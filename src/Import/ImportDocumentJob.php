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
 * Imports/Updates a document.
 *
 * @author Kai
 *        
 */
class ImportDocumentJob extends ImportResourceJob {
	
	/**
	 *
	 * @param Title $title        	
	 * @param array $params
	 *        	job parameters (timestamp)
	 */
	function __construct($title, $params) {
		parent::__construct ( 'ImportDocumentJob', $title, $params );
		$jobID = $this->params['job-id'];
		if (is_null($jobID)) {
			$jobID = 'from-console';
		}
		$this->logger = new LoggerUtils('ImportDocumentJob', 'Import', $jobID);
	}
	
	
	
	/**
	 * Extracts metadata from an Office document (using Apache Tika).
	 * (if ER is installed, otherwise metadata is always empty)
	 *
	 * @param string $filepath        	
	 * @return Array with metadata
	 */
	protected function getDocumentMetadata($filepath) {
		
		if (! defined ( 'US_SEARCH_EXTENSION_VERSION' )) {
			return [ ];
		}
		
		$metadata = [ ];
		$indexer = FSIndexerFactory::create ();
		$docData = $indexer->extractDocument ( $filepath );
		if (is_null($docData ['xml'])) {
			// something is wrong with metadata extraction
			// return empty set of metatdata
			$this->logger->warn("Could not extract metadata: " . $filepath);
			return [ ];
		}
		$metadataXML = $docData ['xml']->xpath ( '//arr' );
		foreach ( $metadataXML as $m ) {
			
			$attribute = ( string ) $m->attributes ()['name'];
			$value = ( string ) $m->str;
			$metadata [$attribute] = $value;
		}
		
		return $metadata;
	}
	
	
}
