<?php

namespace DIQA\Import;

use Job;
use WikiPage;
use Title;
use Revision;
use DIQA\FacetedSearch\FSIndexerFactory;
use SMW\StoreFactory;
use DIQA\Util\TemplateEditor;
use DIQA\Util\LoggerUtils;
use DIQA\Import\Models\TaggingRule;
use DIQA\Import\Models\CrawlerConfig;

/**
 * Imports/Updates a image.
 *
 * @author Kai
 *        
 */
class ImportImageJob extends ImportResourceJob {
	
	/**
	 *
	 * @param Title $title        	
	 * @param array $params
	 *        	job parameters (timestamp)
	 */
	function __construct($title, $params) {
		parent::__construct ( 'ImportImageJob', $title, $params );
		$jobID = $this->params['job-id'];
		if (is_null($jobID)) {
			$jobID = 'from-console';
		}
		$this->logger = new LoggerUtils('ImportImageJob', 'Import', $jobID);
	}
	
	protected function getDocumentMetadata($filepath) {
		// can not read metadata from images
		return [ ];
	}
}
