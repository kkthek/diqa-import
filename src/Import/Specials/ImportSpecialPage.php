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
use Carbon\Carbon;

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
	
	const DIQA_IMPORT_FOLDER = '/opt/DIQA';
	
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
			$this->readMountedFolders(false);
			$this->dispatchRequest ($html);
					
			
		} catch(Exception $e) {
			$html = $this->blade->view ()->make ( "specials.general.import-special-error", 
					[	'msg' => $e->getMessage() ] )->render ();
		}
		$wgOut->addHTML ( $html );
	}
	
	/**
	 * Reads the directories in DIQA_IMPORT_FOLDER and creates a crawler configuration for each.
	 * @param boolean force If false, mounted folders are only searched if there are no crawler configs yet.
	 * 
	 * @throws Exception
	 */
	private function readMountedFolders($force = true) {
		
		global $wgDIQAImportUseDIQAFolder;
		if (isset($wgDIQAImportUseDIQAFolder) && $wgDIQAImportUseDIQAFolder === false) {
			return;
		}
		
		// if not force, only check mounted folders if there are no crawler configs yet.
		if (!$force && count(CrawlerConfig::all()) > 0) {
			return;
		}
		
		// check if DIQA-Import-Folder exists and is readible
		if (!file_exists(self::DIQA_IMPORT_FOLDER) || !is_dir(self::DIQA_IMPORT_FOLDER) || !is_readable(self::DIQA_IMPORT_FOLDER)) {
			throw new Exception(sprintf('Please create folder %s and make it readable for apache or set $wgDIQAImportUseDIQAFolder=false in LocalSettings.php.',
					 self::DIQA_IMPORT_FOLDER));
		}
		
		// check configurations and create new if necessary
		$dh = opendir(self::DIQA_IMPORT_FOLDER);
		while (($filepath = readdir($dh))) {
			
			if (($filepath == '.') || ($filepath == '..') || !is_dir(self::DIQA_IMPORT_FOLDER.'/'.$filepath)) {
				continue;
			}
			
			$config = CrawlerConfig::where('root_path', self::DIQA_IMPORT_FOLDER.'/'.$filepath)->get()->first();
			if (is_null($config)) {
				$config = new CrawlerConfig();
				$config->crawler_type = 'doc-import';
				$config->url_prefix = '';
				$config->root_path = self::DIQA_IMPORT_FOLDER.'/'.$filepath;
				$config->date_to_start = Carbon::now()->startOfDay()->toDateTimeString();
				$config->time_interval = CrawlerConfig::$INTERVALS['daily'];
				$config->documents_processed = 0;
				$config->active = 1;
				$config->save();
			}
			
		}
		closedir($dh);
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
			
			$active = isset($_POST ['diqa_import_active']) && $_POST ['diqa_import_active'] == 'on';
			$rootPath = $_POST ['diqa_import_import_path'];
			$urlPrefix = $_POST ['diqa_url_path_prefix'];
			$time_to_start = $_POST ['diqa_time_to_start'];
			$date_to_start = $_POST ['diqa_date_to_start'];
			$time_interval = $_POST ['diqa_time_interval'];
			$check = false;
			$this->doAddOrUpdateEntry ($active, $rootPath, $urlPrefix, $time_to_start, $date_to_start, $time_interval, $check);
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
		
		if (isset ($_POST['diqa-import-force-crawl'])) {
			$id = $_POST['diqa-import-force-crawl'];
			$this->doForceCrawl ($id, $html );
		}
		
		if (isset($_POST['diqa-import-rescan'])) {
			$this->readMountedFolders();
		}
		
		// -----------------------------------
		// wiki operation commands
		// -----------------------------------
		if (isset($_POST['diqa_import_startRefresh'])) {
			$params = [];
			$specialPageTitle = Title::makeTitle(NS_SPECIAL, 'DIQAImport');
			$job = new RefreshDocumentsJob($specialPageTitle, $params);
			JobQueueGroup::singleton()->push( $job );
			self::removeHintToRefreshSemanticData();
		}
		
		$this->showDefaultContent($html);
	}
	
	
	 /**
	  * Adds or updates an entry.
	  * 
	  * @param bool $active
	  * @param string $rootPath
	  * @param string $urlPrefix
	  * @param string $updateInterval
	  * @throws Exception
	  */
	 private function doAddOrUpdateEntry($active, $rootPath, $urlPrefix, $time_to_start, $date_to_start, $time_interval, $check) {
	 
	 	// validate entry
		if ($check) {
			self::checkDirectory($rootPath);
		}
		
		if (!(is_numeric($time_to_start) && floor($time_to_start) == $time_to_start)) {
			throw new Exception("time_to_start must be numeric: $time_to_start", self::ERROR_NOT_NUMERIC);
		}
		
		if (!(is_numeric($time_interval) && floor($time_interval) == $time_interval)) {
			throw new Exception("time_interval must be numeric: $time_interval", self::ERROR_NOT_NUMERIC);
		}
		
		//TODO: verify date
		
		// create or update it
		$id = $_POST['diqa_import_entry_id'];
		if ($id == '') {
			$entry = new CrawlerConfig ();
			$entry->crawler_type = 'doc-import';
			$entry->root_path = $rootPath;
			$entry->url_prefix = $urlPrefix;
			$entry->last_run_at = '0000-00-00 00:00:00';
			
			$time_to_start = strlen($time_to_start) == 1 ? "0$time_to_start" : $time_to_start;
			$entry->date_to_start = "$date_to_start $time_to_start:00:00";
			$entry->time_interval = $time_interval;
			$entry->documents_processed = 0;
			$entry->active = $active == 1;
			$entry->save ();
		} else {
			$entry = CrawlerConfig::where('id', $id)->get()->first();
			$entry->root_path = $rootPath;
			$entry->url_prefix = $urlPrefix;
			$time_to_start = strlen($time_to_start) == 1 ? "0$time_to_start" : $time_to_start;
			$entry->date_to_start = "$date_to_start $time_to_start:00:00";
			$entry->time_interval = $time_interval;
			$entry->active = $active == 1;
			$entry->save();
		}
	 }
		

	/**
	 * Remove entry
	 * 
	 * @param id
	 */
	 private function doRemoveEntry($id) {
		
		$entry = CrawlerConfig::where('id', $id)->get()->first();
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
	 * Force crawl
	 *
	 * @param id
	 * @param string (out) html
	 */
	private function doForceCrawl($id, & $html) {
	
		$entry = CrawlerConfig::where('id', $id)->get()->first();
		$entry->forceRun();
		$entry->setStatus("FORCE");
		$entry->save();
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
					'needsRefresh' => self::needsHintToRefreshSemanticData(),
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
	
	/**
	 * Checks if refresh hint is needed.
	 * 
	 * @return boolean
	 */
	public static function needsHintToRefreshSemanticData() {
		global $IP;
		return file_exists("$IP/images/.diqa-import-needs-refresh");
	}
	
	/**
	 * Removes refresh hint.
	 * 
	 */
	public static function removeHintToRefreshSemanticData() {
		global $IP;
		@unlink("$IP/images/.diqa-import-needs-refresh");
	}
}