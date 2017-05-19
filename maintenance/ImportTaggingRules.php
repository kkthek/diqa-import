<?php

use DIQA\Util\LoggerUtils;
use DIQA\Import\Specials\TaggingSpecialPage;
use DIQA\Util\Data\TreeNode;

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
		
		$root = $this->convertIntoTreeObject(['/opt/DIQA/test', '/opt/DIQA/test1', '/opt/DIQA/test/test2', '/opt/DIQA/test2/test3']);
		print_r($root->getTreeAsJSON());
	}
	
	private function convertIntoTreeObject($directories) {
		$root = new TreeNode();
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
	
	
}

$maintClass = "ImportTaggingRules";
require_once RUN_MAINTENANCE_IF_MAIN;
