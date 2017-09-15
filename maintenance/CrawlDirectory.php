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
		$this->addOption( 'force', 'Enforces the immediate import of documents', false, false );
	}

	public function execute() {
		$this->logger = new LoggerUtils('CrawlDirectory', 'Import');
		
		if(php_sapi_name() == 'cli') {
		    $this->logger->log('Crawling started.');
		}
		
		$dryRun = $this->hasOption('dry-run');
		$force = $this->hasOption('force');
		
		try {
			if ($this->hasOption('directory')) {
				$this->cleanUp();
				$directory = $this->getOption('directory');
				if (!is_dir($directory) || !is_readable($directory)) {
					throw new Exception("Can not access: $directory");
				}
				$this->importDirectory($directory, $dryRun);

			} else {
				$cache = ObjectCache::getInstance(CACHE_DB);
				$cache->set('DIQA.Import.timestamp', time());
				$this->processRegisteredImportJobs ($dryRun, $force);
			}
		} catch(Exception $e) {
			$this->logger->error($e->getMessage());
		}
	}
	
	/**
	 * Check if the crawler can access files and writes it into a log
	 * @param CrawlerConfig $crawlerConfig
	 */
	private function checkCrawlerConfig($crawlerConfig) {
		$cache = ObjectCache::getInstance(CACHE_DB);
		static $errors = [];
		if (!is_readable($crawlerConfig->getRootPath())) {
			$errors[] = "\n{$crawlerConfig->getRootPath()} is not readable.";
			$crawlerConfig->setStatus("ERROR");
		}
		if (!is_dir($crawlerConfig->getRootPath())) {
			$errors[] = "\n{$crawlerConfig->getRootPath()} is not a directory.";
			$crawlerConfig->setStatus("ERROR");
		}
		$cache->set('DIQA.Import.errors', $errors);
		
		$crawlerConfig->save();
	}
	
	/**
	 * Processes all registered jobs
	 * @param bool $dryRun
	 * @param bool $force
	 */
	private function processRegisteredImportJobs($dryRun = false, $force = false) {
		// read registered crawler jobs
		// and select those which should run
		$toRun = [];
		$entries = CrawlerConfig::all();
		foreach($entries as $e) {
			
			if (!$e->isActive()) {
				continue;
			}
			
			$this->checkCrawlerConfig($e);
			if ($e->getStatus() == 'ERROR') {
				continue;
			}
			
			if ($e->notYetRun() || $e->isForceRun() || $force) {
				$toRun[] = $e;
			} else {
				echo "CrawlDirectory: Next scheduled run of {$e->getRootPath()}: " . $e->getNextRun() . "\n";
				
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
			$r->forceRun(false);
			$r->save();
			$this->logger->log("Creating import jobs for: ".$r->getRootPath());
			$jobsCreated = $this->importDirectory($r->getRootPath(), $dryRun, $r->getId());
			$r->setDocumentsProcessed($r->getDocumentsProcessed() + $jobsCreated);
			$r->setStatus("OK");
			$r->save();
		}
	}
	
	/**
	 * Imports a single directory
	 *
	 * @param $directory
	 * @param $dryRun
	 * @param $jobID (optional)
	 * @return int Number of created import jobs for *new* documents
	 */
	private function importDirectory($directory, $dryRun, $jobID = NULL) {
		ImportSpecialPage::checkDirectory($directory);
		
		$specialPageTitle = Title::makeTitle(NS_SPECIAL, 'DIQAImport');
		
		$params = [];
		$params['directory'] = $directory;
		$params['dry-run'] = $dryRun;
		$params['job-id'] = $jobID;
		$job = new CrawlDirectoryJob($specialPageTitle, $params);
		
		return $job->importDocuments();
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
