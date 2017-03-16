<?php
namespace DIQA\Import\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a crawler configuration.
 * 
 * @author Kai
 *
 */
class CrawlerConfig extends Model {
	
	protected $table = 'diqa_imports_crawler';
	public $timestamps = false;
	
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
	 * Interval between two crawl operation in minutes.
	 * 
	 * @return int
	 */
	public function getRunInterval() {
		return $this->run_interval;
	}
	
	/**
	 * Number of documents processed in last run.
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
		$lastRunTS = strtotime($this->last_run_at);
		return $lastRunTS + $this->run_interval * 60 < time();
	}
	
	/**
	 * Returns timestamp of next run.
	 * Format: YYYY-MM-DD HH:MM:SS
	 * 
	 * @return string
	 */
	public function getNextRun() {
		$lastRunTS = strtotime($this->last_run_at);
		return date('Y-m-d H:i:s', $lastRunTS + $this->run_interval * 60);
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
}