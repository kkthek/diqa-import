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
use DIQA\Util\FileUtils;
use DIQA\Util\LoggerUtils;

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

		// initialize logger
		new LoggerUtils('ImportSpecialPage', 'Import');

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
		global $wgGroupPermissions;

		$authenticated = false;
		foreach($wgUser->getGroups() as $group) {
			if (isset($wgGroupPermissions[$group]['diqa-crawl']) && $wgGroupPermissions[$group]['diqa-crawl'] === true) {
				$authenticated = true;
			}
		}

		if (!$authenticated) {
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

		if (isset($_POST['diqa-deactivate-job'])) {
			$id = $_POST['diqa-deactivate-job'];
			$this->setActivationStatus($id, false, $html );
		}

		if (isset($_POST['diqa-activate-job'])) {
			$id = $_POST['diqa-activate-job'];
			$this->setActivationStatus($id, true, $html );
		}

		if (isset($_GET['showLog'])) {
			$jobID = isset($_GET['jobID']) ? $_GET['jobID'] : false;
			$this->doShowLog($jobID);
			die();
		}

		$this->showDefaultContent($html);
	}

	/**
	 * Echos the last 1000 lines of the given Job-Log. If $jobID === false,
	 * it returns the general Import log.
	 *
	 * @param int $jobID
	 */
	private function doShowLog($jobID) {
		$logDir = LoggerUtils::getLogDir();
		$date = (new \DateTime('now', new \DateTimeZone(date_default_timezone_get())))->format("Y-m-d");
		if ($jobID !== false) {
			$path = "$logDir/Import/Import_{$jobID}_{$date}.log";
		} else {
			$path = "$logDir/Import/Import_{$date}.log";
		}
		if (!file_exists($path)) {
			// use general log as fallback
			$path = "$logDir/Import/Import_{$date}.log";
			if (!file_exists($path)) {
				echo "Log not available";
				return;
			}
		}
		$lines = FileUtils::last_lines($path, 1000);
		echo implode('<br>', $lines);
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
	 * Deactivate job
	 *
	 * @param id
	 * @param bool $status
	 * @param string (out) html
	 */
	private function setActivationStatus($id, $status, & $html) {

		$entry = CrawlerConfig::where('id', $id)->get()->first();
		$entry->active = $status ? 1 : 0;
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
					'crawlerErrors' => self::getCrawlerErrors()
		] )->render ();
	}

	public static function isCrawlCommandCalled() {
		$cache = \ObjectCache::getInstance(CACHE_DB);
		$timestamp = $cache->get('DIQA.Import.timestamp');
		return (time() - $timestamp) < 90;
	}

	public static function getCrawlerErrors() {
		$cache = \ObjectCache::getInstance(CACHE_DB);
		$errors = $cache->get('DIQA.Import.errors');
		$logFileErrorHint = $cache->get('DIQA.Util.logFileNotWriteable');
		if ($logFileErrorHint !== false) {
			$errors[] = "Log file can not be written. Please check $logFileErrorHint";
		}
		if($errors) {
		    return array_filter($errors, function($e) { return $e != ''; });
		} else {
		    return [];
		}
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
		return self::_checkDirectory($directory, $directory);
	}

	public static function _checkDirectory($directory, $originalDir) {

		if (is_dir($directory) && is_readable($directory)) {
			return true;
		}
		if (is_dir($directory) && !is_readable($directory)) {
			throw new Exception("Not readable: $directory", self::ERROR_NOT_READABLE);
		}
		if (!is_dir($directory)) {
			$lastpos = strrpos($directory, '/');
			if ($lastpos === false) {
				throw new Exception("Could not access directory: $originalDir", self::ERROR_NO_DIRECTORY);
			}
			return self::_checkDirectory(substr($directory, 0, $lastpos), $originalDir);
		}
	}


}