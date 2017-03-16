<?php

use DIQA\Import\CrawlDirectoryJob;
use DIQA\Import\Models\CrawlerConfig;
use DIQA\Util\LoggerUtils;
use DIQA\Import\DocumentOperations;
use DIQA\Import\Specials\ImportSpecialPage;

/**
 * Crawls a directory and imports documents: Word, Excel, PDF
 *
 * @ingroup DIQA Import
 */

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class CrawlDirectory extends Maintenance {
	
	public function __construct() {
		parent::__construct ();
		$this->mDescription = "Crawls a directory and imports documents: Word, Excel, PDF";
		$this->addOption( 'directory', 'Directory to crawl', false, true );
		$this->addOption( 'dry-run', 'Dry-run (does not import anything)', false, false );
		
	}

	public function execute() {
		
		$this->logger = new LoggerUtils('CrawlDirectory', 'DIQAimport');
		
		try {
			if ($this->hasOption('directory')) {
				
				$this->cleanUp();
				$directory = $this->getOption('directory');
				if (!is_dir($directory) || !is_readable($directory)) {
					throw new Exception("Can not access: $directory");
				}
				$this->importDirectory($directory);
			} else {
				
				global $IP;
				@touch("$IP/images/.diqa-import"); // hint that it is periodically called
				$dryRun = $this->hasOption('dry-run');
				$this->processRegisteredImportJobs ($dryRun);
	
			}
		} catch(Exception $e) {
			echo "\n ERROR: " . $e->getMessage() . "\n";
			$this->logger->error($e->getMessage());
		}
		
		$this->logger->log("DONE.");
	}
	
	/**
	 * Check if the crawler can access files and writes it into a log
	 * @param CrawlerConfig $crawlerConfig
	 */
	private function checkCrawlerConfig($crawlerConfig) {
		global $IP;
		if (!is_readable($crawlerConfig->getRootPath())) {
			$handle = fopen("$IP/images/.diqa-import", "w");
			fwrite($handle, "\n{$crawlerConfig->getRootPath()} is not readable.");
			fclose($handle);	
		}
		if (!is_dir($crawlerConfig->getRootPath())) {
			$handle = fopen("$IP/images/.diqa-import", "w");
			fwrite($handle, "\n{$crawlerConfig->getRootPath()} is not a directory.");
			fclose($handle);
		}
	}
	
	/**
	 * Processes all registered jobs
	 * @param bool $dryRun
	 */
	private function processRegisteredImportJobs($dryRun) {
		
		// clear error log
		global $IP;
		$handle = fopen("$IP/images/.diqa-import", "w");
		ftruncate($handle, 0);
		fclose($handle);
		
		// read registered crawler jobs
		// and select those which should run
		$toRun = [];
		$entries = CrawlerConfig::all();
		foreach($entries as $e) {
			$this->checkCrawlerConfig($e);
			if ($e->notYetRun()) {
				$toRun[] = $e;
			} else {
				$this->logger->log("\nNext scheduled run of {$e->getRootPath()}: " . $e->getNextRun());
				
				if ($e->shouldRun()) {
					$toRun[] = $e;
				}
			}
		}
		
		if (count($toRun) > 0) {
			$this->cleanUp();
		}
		
		// create import jobs and update 
		// last run
		foreach($toRun as $r) {
			
			$r->updateLastRun();
			$r->setStatus("Creating import jobs...");
			$r->save();
			$this->logger->log("Creating import jobs for: ".$r->getRootPath());
			$jobsCreated = $this->importDirectory($r->getRootPath(), $dryRun);
			$r->setDocumentsProcessed($jobsCreated);
			$r->setStatus("OK");
			$r->save();
		}
	}

	
	/**
	 * Imports a single directory
	 * 
	 * @param $directory
	 * @param $dryRun
	 * @return int Number of created import jobs
	 */
	private function importDirectory($directory, $dryRun) {
		
		ImportSpecialPage::checkDirectory($directory);
		
		$specialPageTitle = Title::makeTitle(NS_SPECIAL, 'DIQAImport');
		
		$params = [];
		$params['directory'] = $directory;
		$job = new CrawlDirectoryJob($specialPageTitle, $params);
		
		return $job->importDocuments($dryRun);
	}
	
	/**
	 * Cleans up all documents imported, ie. remove those
	 * which do not exist anymore in the filesystem.
	 */
	private function cleanUp() {
		
		$this->logger->log("Cleaning up content...");
		$specialPageTitle = Title::makeTitle(NS_SPECIAL, 'DIQAImport');
		
		DocumentOperations::cleanupAllDocuments(0);
	}
}

$maintClass = "CrawlDirectory";
require_once RUN_MAINTENANCE_IF_MAIN;
