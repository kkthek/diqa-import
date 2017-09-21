<?php

namespace DIQA\Import;

use DIQA\Util\Data\TreeNode;
use DIQA\Util\LoggerUtils;
use Job;
use JobQueueGroup;
use SMW\StoreFactory;
use Title;

/**
 * Crawls a directory and imports documents.
 *
 * @author Kai
 *
 */
class CrawlDirectoryJob extends Job {

	/**
	 * @param Title $title
	 * @param array $params job parameters (timestamp)
	 */
	function __construct( $title, $params ) {
		parent::__construct( 'CrawlDirectoryJob', $title, $params );
		$jobID = $this->params['job-id'];
		if (is_null($jobID)) {
			$jobID = 'from-console';
		}
		
		$this->logger = new LoggerUtils('CrawlDirectoryJob', 'Import', $jobID);
	}
	
	/**
	 * implementation of the actual job
	 *
	 * {@inheritDoc}
	 * @see Job::run()
	 */
	public function run() {
	    $this->importDocuments();
	}
	
	/**
	 * Create import jobs.
	 *
	 *
	 * @return int Number of jobs created for *new* documents
	 */
	public function importDocuments() {
	    $directory = $this->params['directory'];
		$specialPageTitle = Title::makeTitle(NS_SPECIAL, 'DIQAImport');
		
		$jobsForNewDocuments = 0;
		$directory = rtrim($directory, ' /');
		$directories = [];

		$this->crawl($directory, function($file) use ($specialPageTitle, & $jobsForNewDocuments) {
			$this->logger->log("Processing file: $file");
			
			$jobID = $this->params['job-id'];
			$cache = \ObjectCache::getInstance(CACHE_DB);
			$cache->set("DIQA.Import.Jobs.$jobID", time());
			
			$ext = pathinfo ( $file, PATHINFO_EXTENSION );
			$ext = strtolower($ext);
				
			global $wgFileExtensions;
			if (!in_array($ext, $wgFileExtensions)) {
				$this->logger->warn("...unsupported file type: $file");
				return;
			}
			
			if (!$this->isModified($file)) {
				$this->logger->log('...not modified, skipping.');
				return;
			}
			
			$isNewDocument = is_null($this->getTitleForFileLocation($file));
			
			$this->logger->log('...creating import job.');
			$params = [];
			$params['filepath'] = $file;
			$params['modtime'] = date('Y-m-d H:i:s', filemtime($file));
			$params['dry-run'] = $this->params['dry-run'];
			$params['job-id'] = $this->params['job-id'];
			
			switch($ext) {
				case "doc":
				case "docx":
				case "xls":
				case "xlsx":
				case "pdf":
				case "ppt":
				case "pptx":
					$job = new ImportDocumentJob($specialPageTitle, $params);
					break;
				default:
					$job = new ImportImageJob($specialPageTitle, $params);
					break;
			}
			
			JobQueueGroup::singleton()->push( $job );
			if ($isNewDocument) {
				$jobsForNewDocuments++;
			}
			
		}, $directories);
		
		// store encountered directories as TreeNode object
		$cache = \ObjectCache::getInstance(CACHE_DB);
		$oldTree = $cache->get('DIQA.Import.directories');
		$tree = $this->convertIntoTreeObject($oldTree, $directories);
		$cache->set('DIQA.Import.directories', $tree);
		return $jobsForNewDocuments;
	}
	
	/**
	 * Stores encountered directories as TreeNode object.
	 *
	 * @param array $directories Paths into filesystem
	 * @return \DIQA\Util\Data\TreeNode
	 */
	 private function convertIntoTreeObject($oldTree, $directories) {
	 	if (is_null($oldTree) || !($oldTree instanceof TreeNode)) {
			$root = new TreeNode();
	 	} else {
	 		$root = $oldTree;
	 	}
		foreach($directories as $f) {
			$currentNode = $root;
			$parts = explode('/', $f);
			foreach($parts as $p) {
				if (trim($p) == '') continue;
				if ($currentNode->containsChildWithTitle($p)) {
					$currentNode = $currentNode->getChildByTitle($p);
				} else {
					$newNode = new TreeNode($p, $p);
					$currentNode->addChild($newNode);
					$currentNode = $newNode;
				}
			}
		}
		return $root;
	}
	
	/**
	 * Checks if the modification timestamp in filesystem
	 * matches the modification timestamp stored in the wiki.
	 *
	 * @param string $filepath
	 * @return boolean true if both timestamps do not match or
	 * 		if the given file is not yet imported.
	 */
	private function isModified($filepath) {
		$title = $this->getTitleForFileLocation($filepath);
		if (is_null($title)) {
			return true;
		}
		$file = wfLocalFile($title);
		if (!$file->exists()) {
			return true;
		}
		
		$timestamp = $this->getTimestamp($title);
		$modtime = filemtime($filepath);
			
		$timeOffset = timezone_offset_get(new \DateTimeZone(date_default_timezone_get()), new \DateTime("now"));
		
		return $modtime + $timeOffset != $timestamp;
	}
	
	/**
	 * Returns the (first) property value of DIQAModificationTime.
	 *
	 * @param Title $title
	 * @return number
	 */
	private function getTimestamp($title) {
		$store = StoreFactory::getStore ();
		$timestamp = $store->getPropertyValues( \SMWDIWikiPage::newFromTitle($title),
				\SMWDIProperty::newFromUserLabel ( 'DIQAModificationTime' ) );
		$timestamp = reset($timestamp);
		return $timestamp !== false ? $timestamp->getMwTimestamp() : 0;
	}
	
	/**
	 * Returns the title of the imported document for a given file location.
	 *
	 * @param string $filepath
	 * @return Title or NULL if it does not exist.
	 */
	private function getTitleForFileLocation($filepath) {
		$store = StoreFactory::getStore ();
		$value = $store->getPropertySubjects(\SMWDIProperty::newFromUserLabel ( 'DIQAFileLocation' ),
			new \SMWDIBlob($filepath) );
		$value = reset($value);
		return $value !== false ? $value->getTitle() : NULL;
	}
	
	/**
	 * Recursively crawls a directory structure.
	 *
	 * @param string $filepath Path
	 * @param function $callback Called for every file.
	 * @param array $directories Returns all directories below $filepath
	 */
	private function crawl($filepath, $callback, &$directories = NULL) {
		if (is_file($filepath)) {
			$callback($filepath);
		} else {
			// file is a directory
			if (!is_null($directories)) $directories[] = $filepath;
			$base_dir = $filepath;
			$dh = opendir($base_dir);
			while (($filepath = readdir($dh))) {
				if (($filepath != '.') && ($filepath != '..')) {
					// call crawl() on the found file/directory
					$this->crawl($base_dir . '/' . $filepath, $callback, $directories);
				}
			}
			closedir($dh);
		}
	}
}
