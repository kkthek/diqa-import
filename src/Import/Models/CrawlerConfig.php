<?php
namespace DIQA\Import\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Exception;

/**
 * Represents a crawler configuration.
 * 
 * @author Kai
 *
 */
class CrawlerConfig extends Model {
	
	protected $table = 'diqa_imports_crawler';
	public $timestamps = false;
	
	public static $INTERVALS = [ 'daily' => 0, 'hourly' => 1];
	
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Filesystem path to a directory
	 * where documents will be imported from.
	 * 
	 * @return string
	 */
	public function getRootPath() {
		return $this->root_path;
	}
	
	/**
	 * URL prefix which will be used on Search page.
	 * It replaces the root_path from above in hyperlinks.
	 * 
	 * @return string
	 */
	public function getURLPrefix() {
		return $this->url_prefix;
	}
	
	/**
	 * Timestamp of last run.
	 * Format: YYYY-MM-DD HH:MM:SS
	 * 
	 * @return string
	 */
	public function getLastRun() {
		return $this->last_run_at;
	}
	
	/**
	 * Time to start crawling
	 * 
	 * @return HH:MM:SS
	 */
	public function getTimeToStart() {
		return date("H:i:s", strtotime($this->date_to_start));
	}
	
	/**
	 * Date to start crawling 
	 * 
	 * @return string YYYY-MM-DD
	 */
	public function getDateToStart() {
		return date("Y-m-d", strtotime($this->date_to_start));
	}
	
	/**
	 * Intervals
	 * 
	 * @return int values of $INTERVALS
	 */
	public function getInterval() {
		return $this->time_interval;
	}
	
	/**
	 * Number of documents processed (=jobs created)
	 * in last run.
	 * 
	 * @return int
	 */
	public function getDocumentsProcessed() {
		return $this->documents_processed;
	}
	
	/**
	 * Status info of last run
	 * 
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}
	
	/**
	 * Updates last_run_at to current timestamp.
	 */
	public function updateLastRun() {
		$this->last_run_at = date('Y-m-d H:i:s', time());
	}
	
	/**
	 * Determines if a crawl config has not been executed yet.
	 * 
	 * @return boolean
	 */
	public function notYetRun() {
		return $this->last_run_at == '0000-00-00 00:00:00';
	}
	
	/**
	 * Determines if a crawl operation is overdue.
	 * @return boolean
	 */
	public function shouldRun() {
		
		if ($this->notYetRun()) {
			return $this->checkStartDateAndTime();
		}
		
		$lastRunTS = strtotime($this->last_run_at);
		$lastRunCarbon = Carbon::createFromTimestamp($lastRunTS);
		
		switch($this->getInterval()) {
			case self::$INTERVALS['daily']: 
				
				if ($lastRunCarbon->addDay()->gt(Carbon::now())) {
					return false;
				}
				return true;
				
				break;
			case self::$INTERVALS['hourly']: 
				
				if ($lastRunCarbon->addHour()->gt(Carbon::now())) {
					return false;
				}
				return true;
				
				break;
				
			default:
				throw new Exception("unknown interval type");
		}
	
		
	}
	
	/**
	 * Checks if current date is after startDate 
	 * AND if current time is after the time in startDate.
	 * 
	 * If startDate is not set, it always returns true.
	 * 
	 * @return boolean
	 */
	private function checkStartDateAndTime() {
		if ($this->date_to_start == '0000-00-00 00:00:00') {
			return true;
		}
		$datetostart = strtotime($this->date_to_start);
		$datetostart = Carbon::createFromTimestamp($datetostart);
		$beginOnCurrentDay = Carbon::now()->startOfDay()->addHours($datetostart->hour);
		$now = Carbon::now();
		return $now->gte($datetostart) && $now->gte($beginOnCurrentDay);
	}
	
	/**
	 * Returns time of next run.
	 *  
	 * @return Carbon
	 */
	public function getNextRun() {
		
		if ($this->notYetRun()) {
			$datetostart = strtotime($this->date_to_start);
			$datetostart = Carbon::createFromTimestamp($datetostart);
			return $datetostart;
		}
		
		$lastRunTS = strtotime($this->last_run_at);
		$lastRunCarbon = Carbon::createFromTimestamp($lastRunTS);
		
		switch($this->getInterval()) {
			case self::$INTERVALS['daily']: 
				return $lastRunCarbon->addDay();
				break;
			case self::$INTERVALS['hourly']: 
				return $lastRunCarbon->addHour();
				break;
			default:
				throw new Exception("unknown interval type");
		}
		
		
	}
	
	/**
	 * Set number of documents processed in last run
	 * (ie. ImportDocumentJobs created)
	 * 
	 * @param int $documentsProcessed
	 */
	public function setDocumentsProcessed($documentsProcessed) {
		$this->documents_processed = $documentsProcessed;
	}
	
	/**
	 * Set Status info of last run
	 * 
	 * @param string $text
	 */
	public function setStatus($text) {
		$this->status_text = $text;
	}
	
	/**
	 * Returns if the crawler config is active, ie. it should be crawled.
	 * 
	 * @return boolean
	 */
	public function isActive() {
		return $this->active == 1;
	}
	
	/**
	 * Set ForceRun state
	 * @param boolean ForceRun state
	 * 
	 * @return boolean
	 */
	public function forceRun($forceRun = true) {
		return $this->force_run = $forceRun ? 1 : 0;
	}
	
	/**
	 * is run forced?
	 *
	 * @return boolean
	 */
	public function isForceRun() {
		return $this->force_run == 1;
	}
	
	/**
	 * Checks if jobs of the current config did run in the last 60s.
	 * 
	 * @return boolean
	 */
	public function isRunning() {
		$cache = \ObjectCache::getInstance(CACHE_DB);
		$timestamp = $cache->get("DIQA.Import.Jobs.{$this->id}");
		return (time() - $timestamp) < 60;
	}
}