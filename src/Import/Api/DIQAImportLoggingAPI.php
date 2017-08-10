<?php
namespace DIQA\Import\Api;

use DIQA\Import\Specials\ImportSpecialPage;
use DIQA\Util\FileUtils;
/**
 * DIQA Logging API
 * 
 * @author Kai
 *        
 */
class DIQAImportLoggingAPI extends \ApiBase {
	
	
	public function __construct($query, $moduleName) {
		parent::__construct ( $query, $moduleName );
		
	}
	public function isReadMode() {
		return false;
	}
	public function execute() {
		$params = $this->extractRequestParams ();
		$command = $params ['command'];
		
		$result = [];
		
		switch($command) {
			
			case "get-log":
				
				$jobID = isset($params['jobID']) ? $params['jobID'] : false;
				$offset = isset($params ['offset']) ? $params ['offset'] : 0;
				$limit = isset($params ['limit']) ? $params ['limit'] : 1000;
				
				$result = $this->getLog($jobID, $offset, $limit);
				
				break;

			case "search-log":
				
				$jobID = isset($params['jobID']) ? $params['jobID'] : false;
				$search = isset($params ['search']) ? $params ['search'] : '';
				$limit = isset($params ['limit']) ? $params ['limit'] : 1000;
				
				$result = $this->searchLog($jobID, $search, $limit);
				
				break;
				
				
			default:
				$this->dieUsage ( $values );
		}
		
		// Set top-level elements.
		$resultElement = $this->getResult ();
		$resultElement->setIndexedTagName ( $result, 'p' );
		$resultElement->addValue ( null, 'diqaimportlog', $result );
	}
	
	private function getLog($jobID, $offset, $limit = 1000) {
		
		$path = ImportSpecialPage::getLogPath($jobID);
		$result = FileUtils::last_lines($path, $limit, $offset);
		$result['lines'] = array_values(array_filter($result['lines'], function($l) { return trim($l) != ''; }));
		return $result;
		
	}
	
	private function searchLog($jobID, $search, $limit = 1000) {
	
		$path = ImportSpecialPage::getLogPath($jobID);
		$command = sprintf('grep "%s" "%s"', $search, $path);
		
		exec($command, $out, $ret);
		if ($ret !== 0) {
			$result['lines'] = [];
			return $result;
		}
		$result['lines'] = array_filter($out, function($l) { return trim($l) != ''; });
		$result['lines'] = array_slice($result['lines'], 0, $limit);
		return $result;
	
	}
	
	protected function getAllowedParams() {
		return array (
				
				'command' => null,
				'offset' => null,
				'limit' => null,
				'jobID' => null,
				'search' => null,
				
		);
	}
	protected function getParamDescription() {
		return array (
				
				'command' => 'Command to execute',
				'offset' => 'Offset in log (bytes)',
				'limit' => 'Limit of lines',
				'job-ID' => 'Job-ID',
		);
	}
	protected function getDescription() {
		return 'DIQA-Util Ajax interface';
	}
	protected function getExamples() {
		return array (
				'api.php?action=diqa_import_log&format=json&command=get-log&job-ID=35' 
		);
	}
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
	
}
	