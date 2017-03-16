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

/**
 * Imports/Updates a document.
 *
 * @author Kai
 *        
 */
class ImportDocumentJob extends Job {
	
	/**
	 *
	 * @param Title $title        	
	 * @param array $params
	 *        	job parameters (timestamp)
	 */
	function __construct($title, $params) {
		parent::__construct ( 'ImportDocumentJob', $title, $params );
		$this->logger = new LoggerUtils('ImportDocumentJob', 'Import');
	}
	
	/**
	 * implementation of the actual job
	 *
	 * {@inheritDoc}
	 *
	 * @see Job::run()
	 */
	public function run() {
		
		
		$filepath = $this->params ['filepath'];
		$modtime = $this->params ['modtime'];
		$dryRun = $this->params ['dry-run'];
		if (! file_exists ( $filepath )) {
			$this->logger->warn("Skip: File does not exist $filepath.");
			return;
		}
		$basename = pathinfo ( $filepath, PATHINFO_BASENAME );
		$filename = pathinfo ( $filepath, PATHINFO_FILENAME );
		$fileExtension = pathinfo ( $filepath, PATHINFO_EXTENSION );
		$fileExtension = strtolower($fileExtension);
		
		// check if title with same name already exists
		$title = Title::newFromText ( $basename, NS_FILE );
		if ($title->exists()) {
			$floc = $this->getFileLocation($title);
			if ($filepath != $floc) {
				// other document with same name but different path!
				// hence, create new unique title
				$title = Title::newFromText ( $filename . ' ' . uniqid() . '.'.$fileExtension, NS_FILE );
			}
		}
		
		$this->logger->log("Importing {$title->getPrefixedText()}...");
		
		// acquire document metadata from SOLR (if available)
		$metadata = $this->getDocumentMetadata ( $filepath );
		$metadata ['DIQAModificationTime'] = $modtime;
		$metadata ['DIQAFileLocation'] = $filepath;
		$metadata ['DIQAFilename'] = $filename;
		$metadata ['DIQAFilesuffix'] = $fileExtension;
		
		$this->storeEncounteredMetadata($metadata);
		
		if ($dryRun) {
			// do not import, just apply tagging rules and log results
			$this->logger->log("DRY-RUN: check tagging rules for [{$title->getPrefixedText()}]");
			$this->checkTaggingRules($metadata);
			return;
		}
		
		$exists = $title->exists (); // save the state if it already existed or not
		                            
		// create/change wiki text
		$wikiContent = $this->createOrUpdateWikipage ( $title, $metadata );
		
		// upload file
		$imFile = wfLocalFile ( $title );
		$status = $imFile->upload ( $filepath, "auto-inserted by DIQAimport crawler", $wikiContent );
		if ($status->isGood() || $status->isOK()) {
			$this->logger->log("Importing {$title->getPrefixedText()} successfully completed.");
		} else {
			$this->logger->error("Importing {$title->getPrefixedText()} FAILED.");
		}
		
		// if necessay update wiki text.
		// For a new article this is already done by File::upload, 
		// for an old it must be done manually
		if ($exists) {
			$oContent = \ContentHandler::makeContent ( $wikiContent, $title );
			$oWikiPage = new WikiPage ( $title );
			$status = $oWikiPage->doEditContent ( $oContent, "auto-inserted by DIQAimport crawler", EDIT_UPDATE );
			if ($status->isGood() || $status->isOK()) {
				$this->logger->log("Updating content of {$title->getPrefixedText()} successfully completed.");
			} else {
				$this->logger->error("Updating content of {$title->getPrefixedText()} FAILED.");
			}
		}
		
		
		$this->logger->log("DONE.");
	}
	
	/**
	 * Creates or updates a document wiki page by adding/updating
	 * a template call of DIQACrawlerDocument with all extracted 
	 * metadata and a few DIQAimport management properties.
	 * 
	 * @param Title $title
	 * @param array $metadata
	 * @return string The wiki content of the page
	 */
	private function createOrUpdateWikipage($title, $metadata) {
		if ($title->exists ()) {
			// if article already exists, change modification timestamp
			$Revision = Revision::newFromTitle ( $title );
			$WikiPageContent = $Revision->getContent ( Revision::RAW )->serialize ();
			$editor = new TemplateEditor ( $WikiPageContent );
			if (strpos ( $WikiPageContent, 'DIQACrawlerDocument' ) !== false) {
				// change modification timestamp
				$wikiContent = $editor->replaceTemplateParameters ( 'DIQACrawlerDocument', $metadata );
			} else {
				// template is missing. should normally not happen
				// re-add it in this case
				$editor = new TemplateEditor ();
				$wikiContent = $editor->serializeTemplate ( 'DIQACrawlerDocument', $metadata );
			}
		} else {
			// article does not exist, so add the template with modification timestamp
			$editor = new TemplateEditor ();
			$wikiContent = $editor->serializeTemplate ( 'DIQACrawlerDocument', $metadata );
		}
		return $wikiContent;
	}
	
	/**
	 * Extracts metadata from an Office document (using Apache Tika).
	 * (if ER is installed, otherwise metadata is always empty)
	 *
	 * @param string $filepath        	
	 * @return Array with metadata
	 */
	private function getDocumentMetadata($filepath) {
		
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
	
	/**
	 * Returns the (first) property value of DIQAFileLocation.
	 *
	 * @param Title $title
	 * @return string
	 */
	private function getFileLocation($title) {
		$store = StoreFactory::getStore ();
		$value = $store->getPropertyValues( \SMWDIWikiPage::newFromTitle($title),
				\SMWDIProperty::newFromUserLabel ( 'DIQAFileLocation' ) );
		$value = reset($value);
		return $value !== false ? $value->getString() : '';
	}
	
	/**
	 * Applies tagging rules for a crawled property and logs the results.
	 * 
	 * @param array $metadata
	 */
	private function checkTaggingRules($metadata) {
		foreach($metadata as $property => $value) {
			$rules = TaggingRule::where('crawled_property', $property)
			->orderBy('priority')
			->get();
			
			$output = '';
			foreach($rules as $rule) {
				$lastRule = $rule;
				TaggingRuleParserFunction::applyRule($rule, $metadata, $output);
				
				if ($output != '') {
					break;
				}
			}
			if ($output != '') {
				$this->logger->log("Applied rule: {$lastRule->toString()}");
				$this->logger->log("Crawled property {$property}={$value} yields '{$output}'");
			}
		}
	}
	
	private function storeEncounteredMetadata($metadata) {
		
		global $IP;
		$filename = "$IP/images/.diqa-import-metadata";
		static $currentMetadata;
		
		if (is_null($currentMetadata)) {
			$content = file_exists($filename) && is_readable($filename) ? file_get_contents($filename) : '';
			$currentMetadata = explode(',', $content);
		}
		
		$newmetadata = array_unique(array_merge(array_keys($metadata), $currentMetadata));
		
		if (count($newmetadata) > count($currentMetadata)) {
			$handle = @fopen($filename, "w");
			if ($handle !== false) {
				fwrite($handle, implode(",", $newmetadata));
				fclose($handle);
			}
			$currentMetadata = $newmetadata;
		}
	}
}
