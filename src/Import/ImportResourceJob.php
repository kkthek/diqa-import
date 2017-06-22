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

abstract class ImportResourceJob extends Job {
	
	protected $logger;
	
	/**
	 * Reads metadata from the given file resource.
	 * 
	 * @param string $filepath
	 */
	protected abstract function getDocumentMetadata($filepath);
	
	
	/**
	 * implementation of the actual job
	 *
	 * {@inheritDoc}
	 *
	 * @see Job::run()
	 */
	public function run() {
	
		$jobID = $this->params ['job-id'];
		$filepath = $this->params ['filepath'];
		$modtime = $this->params ['modtime'];
		$dryRun = $this->params ['dry-run'];
	
		$crawlerConfig = CrawlerConfig::where('id', $jobID)->get()->first();
		if (!$crawlerConfig->isActive()) {
			return;
		}
	
		$cache = \ObjectCache::getInstance(CACHE_DB);
		$cache->set("DIQA.Import.Jobs.$jobID", time());
	
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
		$metadata ['DIQAFilePath'] = dirname($filepath);
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
	 * Returns the (first) property value of DIQAFileLocation.
	 *
	 * @param Title $title
	 * @return string
	 */
	protected function getFileLocation($title) {
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
	protected function checkTaggingRules($metadata) {
		foreach($metadata as $property => $value) {
			$rules = TaggingRule::where('crawled_property', $property)
			->orderBy('priority')
			->get();
				
			$output = '';
			$info = null;
			foreach($rules as $rule) {
				$lastRule = $rule;
				TaggingRuleParserFunction::applyRule($rule, $metadata, $output, $info);
	
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
	
	protected function storeEncounteredMetadata($metadata) {
	
		$cache = \ObjectCache::getInstance(CACHE_DB);
		static $currentMetadata;
	
		if (is_null($currentMetadata)) {
			$currentMetadata = $cache->get('DIQA.Import.metadataProperties');
			if ($currentMetadata === false) {
				$currentMetadata = [];
			}
		}
	
		$newmetadata = array_unique(array_merge(array_keys($metadata), $currentMetadata));
	
		if (count($newmetadata) > count($currentMetadata)) {
			$cache->set('DIQA.Import.metadataProperties', $newmetadata);
			$currentMetadata = $newmetadata;
			$this->logger->log("Updated DIQA.Import.metadataProperties in ObjectCache");
		}
	}
}