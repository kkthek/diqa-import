<?php

namespace DIQA\Import\Specials;

use SMW\SpecialPage;
use Philo\Blade\Blade;
use Title;
use JobQueueGroup;
use Revision;
use Exception;

use DIQA\Import\Models\CrawlerConfig;
use DIQA\Import\Models\TaggingRule;
use DIQA\Import\RefreshDocumentsJob;
use DIQA\Util\TemplateEditor;
use DIQA\Import\TaggingRuleParserFunction;

if (! defined ( 'MEDIAWIKI' )) {
	die ();
}
class ImportSpecialPage extends SpecialPage {
	
	const ERROR_NOT_ALLOWED = 100;
	const ERROR_NO_DIRECTORY = 101;
	const ERROR_NOT_READABLE = 102;
	const ERROR_NOT_NUMERIC = 103;
	const ERROR_WRONG_REGEX = 104;
	const ERROR_INTERNAL = 105;
	
	private $blade;
	public function __construct() {
		parent::__construct ( 'DIQAImport' );
		global $wgHooks;
		$views = __DIR__ . '/../../../views';
		$cache = __DIR__ . '/../../../cache';
		
		$this->blade = new Blade ( $views, $cache );
		
	}
	
	/**
	 * Overloaded function that is responsible for the creation of the Special Page
	 */
	public function execute($par) {
		global $wgOut, $wgRequest, $wgServer, $wgScriptPath;
		
		$wgOut->setPageTitle ( wfMessage ( 'diqa-import-title' )->text () );
		
		try {
			
			$html = '';
			$this->checkPriviledges();
			$this->dispatchRequest ($html);
					
			
		} catch(Exception $e) {
			$html = $this->blade->view ()->make ( "specials.general.import-special-error", 
					[	'msg' => $e->getMessage() ] )->render ();
		}
		$wgOut->addHTML ( $html );
	}
	
	/**
	 * Checks if the user is allowed to access.
	 * 
	 * @throws Exception if not
	 */
	private function checkPriviledges() {
		global $wgUser;
		if (!in_array('sysop', $wgUser->getGroups())) {
			throw new Exception('Not allowed to access', self::ERROR_NOT_ALLOWED);
		}
	}
	
	/**
	 * Handles a web-request.
	 * 
	 */
	private function dispatchRequest(& $html) {
		
		// -----------------------------------
		// crawler config commands
		// ----------------------------------- 
		if (isset ( $_POST ['add-import-entry'] )) {
			$rootPath = $_POST ['diqa_import_import_path'];
			$urlPrefix = $_POST ['diqa_url_path_prefix'];
			$updateInterval = $_POST ['diqa_update_interval'];
			$check = false;
			$this->doAddOrUpdateEntry ($rootPath, $urlPrefix, $updateInterval, $check);
		}
		if (isset ($_POST['diqa-import-remove-entry'])) {
			$id = $_POST['diqa-import-remove-entry'];
			$this->doRemoveEntry ($id);
		}
		if (isset ($_POST['diqa-import-edit-entry'])) {
			$id = $_POST['diqa-import-edit-entry'];
			$this->doEditEntry ($id, $html );
			return;
		}
		
		$this->showDefaultContent($html);
	}
	
	
	 /**
	  * Adds or updates an entry.
	  * 
	  * @param string $rootPath
	  * @param string $urlPrefix
	  * @param string $updateInterval
	  * @throws Exception
	  */
	 private function doAddOrUpdateEntry($rootPath, $urlPrefix, $updateInterval, $check) {
	 
	 	// validate entry
		if ($check) {
			self::checkDirectory($rootPath);
		}
		
		if (!(is_numeric($updateInterval) && floor($updateInterval) == $updateInterval)) {
			throw new Exception("Update interval must be numeric: $updateInterval", self::ERROR_NOT_NUMERIC);
		}
		
		// create or update it
		$id = $_POST['diqa_import_entry_id'];
		if ($id == '') {
			$entry = new CrawlerConfig ();
			$entry->crawler_type = 'doc-import';
			$entry->root_path = $rootPath;
			$entry->url_prefix = $urlPrefix;
			$entry->last_run_at = '0000-00-00 00:00:00';
			$entry->run_interval = $updateInterval;
			$entry->documents_processed = 0;
			$entry->save ();
		} else {
			$entry = CrawlerConfig::where('id', $id)->get()->first();
			$entry->root_path = $rootPath;
			$entry->url_prefix = $urlPrefix;
			$entry->run_interval = $updateInterval;
			$entry->save();
		}
	 }
		

	/**
	 * Remove entry
	 * 
	 * @param id
	 */
	 private function doRemoveEntry($id) {
		
		$entry = CrawlerConfig::where('id', $id);
		$entry->delete();
	}

	/**
	 * Edit entry
	 * 
	 * @param id
	 * @param string (out) html
	 */
	 private function doEditEntry($id, & $html) {
		
		$entry = CrawlerConfig::where('id', $id)->get()->first();
		$html .= $this->blade->view ()->make ( "specials.import.import-special-form", 
				[	'entry' => $entry,
					'edit' => true ] )->render ();
	}
	
	/**
	 * Shows the default content of the DIQAimport special page.
	 * 
	 * @param string (out) $html
	 */
	private function showDefaultContent(& $html) {
		$crawlerConfigs = CrawlerConfig::all ();
		$taggingRules = TaggingRule::where('id', '>', 0)
			->orderBy('rule_class')
			->orderBy('priority')
			->get();
		
		$html = $this->blade->view ()->make ( "specials.import.import-special-page",
				[	'entries' => $crawlerConfigs, 
					'taggingRules' => $taggingRules,
					'isCrawlerActive' => self::isCrawlCommandCalled(), 
					'crawlerErrors' => self::getCrawlerErrors()
		] )->render ();
	}
	
	public static function isCrawlCommandCalled() {
		global $IP;
		$modtime = @filemtime("$IP/images/.diqa-import");
		return (time() - $modtime) < 90;
	}
	
	public static function getCrawlerErrors() {
		global $IP;
		$errors = file_get_contents("$IP/images/.diqa-import");
		return array_filter(explode("\n",$errors), function($e) { return $e != ''; });
	}
	
	/**
	 * Checks if the directory exists and is readible (for the caller).
	 * If a parent folder of the given directory is not readible, 
	 * it throws an according exception because it can not decide 
	 * if the folder exists or not.
	 * 
	 * @param string $directory
	 * @throws Exception
	 * @return boolean true if directory exists and is readible
	 */
	public static function checkDirectory($directory) {
		if (is_dir($directory) && is_readable($directory)) {
			return true;
		}
		if (is_dir($directory) && !is_readable($directory)) {
			throw new Exception("Not readable : $directory", self::ERROR_NOT_READABLE);
		}
		if (!is_dir($directory)) {
			$lastpos = strrpos($directory, '/');
			if ($lastpos === false) {
				throw new Exception("Not a directory : $directory", self::ERROR_NO_DIRECTORY);
			}
			return self::checkDirectory(substr($directory, 0, $lastpos));
		}
	}
}