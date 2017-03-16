<?php
namespace DIQA\Import;

use Job;
use DIQA\Util\BackgroundProcess;

class RefreshDocumentsJob extends Job {
	
	/**
	 *
	 * @param Title $title
	 * @param array $params
	 *        	job parameters (timestamp)
	 */
	function __construct($title, $params) {
		parent::__construct ( 'RefreshDocumentsJob', $title, $params );
	}
	
	/**
	 * implementation of the actual job
	 *
	 * {@inheritDoc}
	 *
	 * @see Job::run()
	 */
	public function run() {
		global $IP;
		$command = "php $IP/extensions/SemanticMediaWiki/maintenance/rebuildData.php";
		BackgroundProcess::open($command);
	}
}