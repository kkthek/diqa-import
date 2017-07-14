<?php

use DIQA\Util\LoggerUtils;
use DIQA\Import\Specials\TaggingSpecialPage;

/**
 * Imports tagging rules from an XML file.
 *
 * @ingroup DIQA Import
 */

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class ImportTaggingRules extends Maintenance {
	
	public function __construct() {
		
		parent::__construct ();
		$this->mDescription = "Imports tagging rules from XML.";
		$this->addOption( 'file', 'File to import', true, true );
		
	}

	public function execute() {
		
		$this->logger = new LoggerUtils('ImportTaggingRules', 'Import');
		
		try {
			
			$file = $this->getOption('file');
			
			if (!file_exists($file)) {
				throw new Exception("File does not exist.");
			}
			if (!is_readable($file)) {
				throw new Exception("File is not readable.");
			}
			
			$this->logger->log("Import Tagging rules...");
			TaggingSpecialPage::doImportTaggingRules($file);
			$this->logger->log("Note: After import of tagging rules you have to refresh the semantic data.");
			
		} catch(Exception $e) {
			$this->logger->error($e->getMessage());
		}
		
		$this->logger->log("DONE.");
	}
	
	
}

$maintClass = "ImportTaggingRules";
require_once RUN_MAINTENANCE_IF_MAIN;
