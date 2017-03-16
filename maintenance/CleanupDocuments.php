<?php

use DIQA\Import\CrawlDirectoryJob;
use DIQA\Util\LoggerUtils;
use DIQA\Import\DocumentOperations;

/**
 * Cleans the set of imported documents and removes those which 
 * do not exist anymore.
 *
 * @ingroup DIQA Import
 */
require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class CleanupDocuments extends Maintenance {
	
	public function __construct() {
		parent::__construct ();
		$this->mDescription = "Cleans the set of imported documents and removes those which do not exist anymore";
		$this->addOption ( 'delay', 'Delay between two chunks (in seconds)', false, true );
		
	}
	
	public function execute() {
		
		$this->logger = new LoggerUtils('CleanupDocuments', 'Import');
		
		$delay = $this->hasOption ( 'delay' ) ? $this->params ['delay'] : 0;
		$specialPageTitle = Title::makeTitle(NS_SPECIAL, 'DIQAImport');
		
		$this->logger->log("Cleaning up content...");
		$filesDeleted = DocumentOperations::cleanupAllDocuments($delay);
		$this->logger->log("Total number of deleted files: $filesDeleted");
	
	}
}

$maintClass = "CleanupDocuments";
require_once RUN_MAINTENANCE_IF_MAIN;
