<?php

namespace DIQA\Import;

use Job;
use WikiPage;
use Title;
use JobQueueGroup;
use SMW\StoreFactory;
use DIQA\Util\QueryUtils;
use DIQA\Util\LoggerUtils;

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
		$this->logger = new LoggerUtils('CrawlDirectoryJob', 'Import');
		
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
	 * @param $dryRun
	 * @return int Number of created jobs
	 */
	public function importDocuments($dryRun = false) {
		$directory = $this->params['directory'];
		$specialPageTitle = Title::makeTitle(NS_SPECIAL, 'DIQAImport');
		
		$jobsCreated = 0;
		$directory = rtrim($directory, ' /');
		$directories = [];
		$this->crawl($directory, function($file) use ($specialPageTitle, & $jobsCreated, $dryRun) {
				
			$this->logger->log("Processing file: " . $file);
			
			$ext = pathinfo ( $file, PATHINFO_EXTENSION );
			$ext = strtolower($ext);
				
			switch($ext) {
		
				case "doc":
				case "docx":
				case "xls":
				case "xlsx":
				case "pdf":
				case "ppt":
				case "pptx":
						
					if (!$this->isModified($file)) {
						$this->logger->log("Not modified, skipping: $file");
						return;
					}
						
					$this->logger->log("Create import job for: $file");
					$params = [];
					$params['filepath'] = $file;
					$params['modtime'] = date('Y-m-d H:i:s', filemtime($file));
					$params['dry-run'] = $dryRun;
					$job = new ImportDocumentJob($specialPageTitle, $params);
					JobQueueGroup::singleton()->push( $job );
					$jobsCreated++;
					break;
						
					default:
					
					$this->logger->warn("Not supported filetype: $file");
		
					
					break;
					}
		}, $directories);
		
		// store encountered directories
		global $IP;
		$handle = @fopen("$IP/images/.diqa-import-directories", "w");
		if ($handle !== false) {
			fwrite($handle, implode("\n", $directories));
			fclose($handle);
		}
		
		return $jobsCreated;
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
		
		return $modtime != $timestamp;
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
