<?php

use DIQA\Util\DBHelper;
/**
 * Setups DIQAimport
 *
 * @ingroup DIQA Import
 */
require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class SetupDIQAImport extends Maintenance {
	
	public function __construct() {
		parent::__construct ();
		$this->mDescription = "Setup DIQAimport";
	}
	
	public function execute() {
		$verbose = PHP_SAPI === 'cli' && ! defined ( 'UNITTEST_MODE' );
		wfDIQAInitializeEloquent();
		
		$db = wfGetDB( DB_MASTER );
		$table = $db->tableName('diqa_imports_crawler');
		DBHelper::setupTable($table, array(
			'id' 	=> 'INT(8) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
			'crawler_type' => 'VARCHAR(255) NOT NULL',
			'root_path' => 'VARCHAR(255) NOT NULL',
			'url_prefix' => 'VARCHAR(255) NOT NULL',
			'last_run_at' => 'DATETIME',
			'run_interval' => 'INT(8) NOT NULL',
			'documents_processed' => 'INT(8) NOT NULL',
			'status_text' => 'VARCHAR(4095)'),
		$db, true);
		
		$table = $db->tableName('diqa_imports_taggingrules');
		DBHelper::setupTable($table, array(
			'id' 	=> 'INT(8) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
			'rule_class' => 'VARCHAR(255) NOT NULL',
			'type' => 'VARCHAR(255) NOT NULL',
			'parameters' => 'VARCHAR(255) NOT NULL',
			'return_value' => 'VARCHAR(255)',
			'priority' => 'INT(8) NOT NULL',
			'crawled_property' => 'VARCHAR(255) NOT NULL'),
		$db, true);
		
		echo "\nSetting up wiki pages ... ";
		$title = Title::newFromText ( "DIQACrawlerDocument", NS_TEMPLATE );
		$this->createOrUpdateTitle ( $title, file_get_contents ( '../resources/DIQACrawlerDocument.wiki' ) );
	
		$title = Title::newFromText ( "DIQAModificationTime", SMW_NS_PROPERTY );
		$this->createOrUpdateTitle ( $title, file_get_contents ( '../resources/DIQAModificationTime.wiki' ) );
		
		$title = Title::newFromText ( "DIQAFileLocation", SMW_NS_PROPERTY );
		$this->createOrUpdateTitle ( $title, file_get_contents ( '../resources/DIQAFileLocation.wiki' ) );
		
		$title = Title::newFromText ( "DIQAFilename", SMW_NS_PROPERTY );
		$this->createOrUpdateTitle ( $title, file_get_contents ( '../resources/DIQAFilename.wiki' ) );
		
		$title = Title::newFromText ( "DIQAFilesuffix", SMW_NS_PROPERTY );
		$this->createOrUpdateTitle ( $title, file_get_contents ( '../resources/DIQAFilesuffix.wiki' ) );
		echo "\n\n";
	}
	
	private function createOrUpdateTitle($title, $FileContent) {
		$verbose = PHP_SAPI === 'cli' && ! defined ( 'UNITTEST_MODE' );
		$oContent = ContentHandler::makeContent ( $FileContent, $title );
		$oWikiPage = new WikiPage ( $title );
		if ($oWikiPage->exists ()) {
			$Revision = Revision::newFromTitle ( $title );
			$WikiPageContent = $Revision->getContent ( Revision::RAW )->serialize (); // or: $WikiMarkup = WikiPage::getContent(...)->serialize();
			
			if (strcmp ( trim($WikiPageContent), trim($FileContent) ) == 0) {
				echo "\n   ... ignoring " . $title->getPrefixedText () . " -- It has same content as wiki page.";
				return;
			} else {
				
				$Result = $oWikiPage->doEditContent ( $oContent, "auto-inserted by DIQAimport", EDIT_UPDATE );
				if ($Result->ok) {
					echo "\n   ... updating " . $title->getPrefixedText ();
				} else {
					echo "\n   ... updating " . $title->getPrefixedText () . " <- Error!";
				}
			}
		} else {
			
			$Result = $oWikiPage->doEditContent ( $oContent, "auto-inserted by DIQAimport", EDIT_NEW );
			if ($Result->ok) {
				if ($verbose)
					echo "\n   ... creating " . $title->getPrefixedText ();
			} else {
				if ($verbose)
					echo "\n   ... creating " . $title->getPrefixedText () . " <- Error!";
			}
		}
		
	}
}

$maintClass = "SetupDIQAImport";
require_once RUN_MAINTENANCE_IF_MAIN;
