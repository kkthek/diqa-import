<?php

namespace DIQA\Import\Api;

use DIQA\Import\Specials\ImportSpecialPage;
use Philo\Blade\Blade;
use DIQA\Import\Models\CrawlerConfig;
use DIQA\Util\Data\TreeNode;
/**
 * DIQA import API ajax interface.
 * 
 * @author Kai
 *        
 */
class DIQAImportAPI extends \ApiBase {
	
	private $blade;
	
	public function __construct($query, $moduleName) {
		parent::__construct ( $query, $moduleName );
		$views = __DIR__ . '/../../../views';
		$cache = __DIR__ . '/../../../cache';
		
		$this->blade = new Blade ( $views, $cache );
	}
	public function isReadMode() {
		return false;
	}
	public function execute() {
		$params = $this->extractRequestParams ();
		$command = $params ['command'];
		
		$result = [];
		
		switch($command) {
			
			case "crawler-status":
				
				$result['html'] = $this->blade->view ()->make ( "specials.import.import-special-error-tag",
					[	
					'isCrawlerActive' => ImportSpecialPage::isCrawlCommandCalled(),
					'crawlerErrors' => ImportSpecialPage::getCrawlerErrors()
					] )
				->render ();
				break;

			case "get-folder-picker":
				
				$cache = \ObjectCache::getInstance(CACHE_DB);
				$tree = $cache->get('DIQA.Import.directories');
				
				$result['html'] = $this->blade->view ()->make ( "specials.dialogs.import-special-folder-dialog",
				[ 
					'tree' => $tree->getTreeAsJSON(),
				])
				->render ();
				
				break;
				
			default:
				$this->dieUsage ( $values );
		}
		
		// Set top-level elements.
		$resultElement = $this->getResult ();
		$resultElement->setIndexedTagName ( $result, 'p' );
		$resultElement->addValue ( null, 'diqaimport', $result );
	}
	
	protected function getAllowedParams() {
		return array (
				
				'command' => null,
				
		);
	}
	protected function getParamDescription() {
		return array (
				
				'command' => 'Command to execute',
			
		);
	}
	protected function getDescription() {
		return 'DIQA-Import Ajax interface';
	}
	protected function getExamples() {
		return array (
				'api.php?action=diqa_import&format=json&command=crawler-status' 
		);
	}
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
	
}
	