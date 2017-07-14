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
use DIQA\Util\QueryUtils;
use DIQA\Import\Models\TaggingRuleParameter;

if (! defined ( 'MEDIAWIKI' )) {
	die ();
}
class TaggingSpecialPage extends SpecialPage {
	
	const ERROR_NOT_ALLOWED = 100;
	const ERROR_NO_DIRECTORY = 101;
	const ERROR_NOT_READABLE = 102;
	const ERROR_NOT_NUMERIC = 103;
	const ERROR_WRONG_REGEX = 104;
	const ERROR_INTERNAL = 105;
	
	private $blade;
	public function __construct() {
		parent::__construct ( 'DIQATagging' );
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
		
		$wgOut->setPageTitle ( wfMessage ( 'diqa-tagging-title' )->text () );
		
		try {
			$html = '';
			$this->checkPrivileges();
			$this->dispatchRequest ($html);
		} catch(Exception $e) {
			$html = $this->blade->view ()->make ( "specials.general.import-special-error",
					[	'msg' => $e->getMessage() ] )->render ();
		}
		$wgOut->addHTML ( $html );
	}
	
	/**
	 * Adds Javascript data required by the taggingrule form.
	 * 
	 */
	public static function addJSData() {
		global $dimgAttributeReturnValueMapping, $dimgTitleAttribute;
		$results = [];
		foreach($dimgAttributeReturnValueMapping as $attribute => $queryData) {
			$query = $queryData['query'];
			$titleProperty = $dimgTitleAttribute;
			$synonymProperty = wfMessage('diqa-import-tagging-synonyms')->text();
			$printoutSynonym = new \SMWPrintRequest ( \SMWPrintRequest::PRINT_PROP, "$synonymProperty",
					\SMWPropertyValue::makeUserProperty ( $synonymProperty ) );
			
			$printoutTitle = new \SMWPrintRequest ( \SMWPrintRequest::PRINT_PROP, "$titleProperty", \SMWPropertyValue::makeUserProperty ( $titleProperty ) );
			$query_result = QueryUtils::executeBasicQuery ( $query, [
			$printoutTitle, $printoutSynonym
			], [
			'limit' => 500
			] );
			
			
			$results[$attribute] = [];
			while ( $res = $query_result->getNext () ) {
				$pageID = $res [0]->getNextText ( SMW_OUTPUT_WIKI );
				$pageTitle = $res [1]->getNextText ( SMW_OUTPUT_WIKI );
				
				if (!is_string($pageTitle)) {
					continue;
				}
				
				$synonyms = [];
				while ($synonym = $res [2]->getNextText ( SMW_OUTPUT_WIKI )) {
					$synonyms[] = $synonym;
				}
					
				$mwTitle = \Title::newFromText ( $pageID );
				$pageTitle = \Title::newFromText($pageTitle);
				$pageTitle = isset($queryData['no-namespace']) ? $pageTitle->getText(): $pageTitle->getPrefixedText();
				$results [$attribute][$mwTitle->getPrefixedText()] = [
					'title' => $pageTitle,
					'mwTitle' => $mwTitle->getPrefixedText(),
					'synonyms' => $synonyms
				];
			}
			
		}
		
		global $wgOut;
		$script = "";
		$script .= "\nvar DIQA = DIQA || {};";
		$script .= "\nDIQA.IMPORT = DIQA.IMPORT || {};";
		$script .= "\nDIQA.IMPORT.AttributeReturnValueMapping = ".json_encode($results).";";
		$wgOut->addScript(
				'<script type="text/javascript">'.$script.'</script>');
	}
	
	/**
	 * Checks if the user is allowed to access.
	 *
	 * @throws Exception if not
	 */
	private function checkPrivileges() {
		global $wgUser;
		global $wgGroupPermissions;
		
		$authenticated = false;
		foreach($wgUser->getGroups() as $group) {
			if (isset($wgGroupPermissions[$group]['diqa-tag']) && $wgGroupPermissions[$group]['diqa-tag'] === true) {
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
		// tagging rule commands
		// -----------------------------------
		if (isset( $_POST['add-import-taggingrule'])) {
			$attribute = $_POST ['diqa_taggingrule_attribute'];
			$crawledProperty = $_POST ['diqa_taggingrule_crawledProperty'];
			$type = $_POST ['diqa_taggingrule_type'];
			$parameters = $_POST ['diqa_taggingrule_parameters'];
			$priority = $_POST ['diqa_taggingrule_priority'];
			$returnValue = $_POST ['diqa_taggingrule_returnvalue'];
			$this->doAddOrUpdateTaggingRule ($attribute, $crawledProperty, $type, $parameters, $returnValue, $priority);
		}

		if (isset ($_POST['diqa-import-remove-rule'])) {
			$id = $_POST['diqa-import-remove-rule'];
			$this->doRemoveTaggingRule ($id);
		}
		
		if (isset ($_POST['diqa-import-edit-rule'])) {
			$id = $_POST['diqa-import-edit-rule'];
			$this->doEditTaggingRule ($id, $html );
			return;
		}
		
		if (isset ($_POST['diqa-import-copy-rule'])) {
			$id = $_POST['diqa-import-copy-rule'];
			$copy = $this->doCopyTaggingRule ($id, $html );
			$this->doEditTaggingRule ($copy->id, $html );
			return;
		}
		
		if (isset ($_POST['diqa-import-test-rule'])
			&& !isset($_POST['cancel-import-taggingrule'])) {
			$id = $_POST['diqa-import-test-rule'];
			$article = isset($_POST['diqa_taggingrule_testarticle']) ? $_POST['diqa_taggingrule_testarticle'] : '';
			$pageId = isset($_POST['diqa_taggingrule_testarticle_pageid']) ? $_POST['diqa_taggingrule_testarticle_pageid'] : '';
			
			// tested rule
			$testedRule = new TaggingRule();
			$testedRule->id = 0;
			if (isset($_POST ['diqa_taggingrule_attribute'])) {
				$testedRule->id = $id;
				$attribute = $_POST ['diqa_taggingrule_attribute'];
				$crawledProperty = $_POST ['diqa_taggingrule_crawledProperty'];
				$type = $_POST ['diqa_taggingrule_type'];
				$parameters = $_POST ['diqa_taggingrule_parameters'];
				$priority = $_POST ['diqa_taggingrule_priority'];
				$returnValue = $_POST ['diqa_taggingrule_returnvalue'];
				$testedRule->rule_class = $attribute;
				$testedRule->crawled_property = $crawledProperty;
				$testedRule->type = $type;
				$testedRule->parameters = $parameters;
				$testedRule->priority = $priority;
				$testedRule->return_value = $returnValue;
			}
			
			$this->doTestTaggingRule ($id, $article, $pageId, $testedRule, $html );
			return;
		}
		
		if (isset($_POST['diqa-import-exporttagging'])) {
			$xml = $this->doExportTaggingRules();
			header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
			header("Cache-Control: public"); // needed for internet explorer
			header("Content-Type: application/xml");
			header("Content-Transfer-Encoding: Binary");
			header("Content-Length:".strlen($xml));
			$date = date('Y-m-d', time());
			header("Content-Disposition: attachment; filename=tagging-rules-$date.xml");
			echo $xml;
			die();
		}
		
		if (isset($_FILES['diqa-import-importtagging'])) {
			$file = $_FILES['diqa-import-importtagging']['tmp_name'];
			self::doImportTaggingRules ( $file );
		}
		
		// -----------------------------------
		// wiki operation commands
		// -----------------------------------
		if (isset($_POST['diqa_import_startRefresh'])) {
			$params = [];
			$specialPageTitle = Title::makeTitle(NS_SPECIAL, 'DIQATagging');
			$job = new RefreshDocumentsJob($specialPageTitle, $params);
			JobQueueGroup::singleton()->push( $job );
			self::removeHintToRefreshSemanticData();
		}
		
		$this->showDefaultContent($html);
	}
	
	public static function doReorderTaggingRules($ruleIDs) {
		$priority = 1;
		foreach($ruleIDs as $ruleID) {
			$entry = TaggingRule::where('id', $ruleID)->get()->first();
			$entry->priority = $priority;
			$entry->save();
			$priority++;
		}
		self::addHintToRefreshSemanticData();
	}
		
	private function doAddOrUpdateTaggingRule($attribute, $crawledProperty, $type, $parameters, $returnValue, $priority) {
			
		// validate entry
		if (!(is_numeric($priority) && floor($priority) == $priority)) {
			throw new Exception("Priority must be numeric: $priority", self::ERROR_NOT_NUMERIC);
		}
	
		if (($type == 'regex' || $type == 'regex-path')) {
			foreach($parameters as $param) {
				$param = str_replace('/','\/', $param);
				if (@preg_match("/$param/", null) === false) {
					throw new Exception("Regex is wrong: $param", self::ERROR_WRONG_REGEX);
				}
			}
		}
	
		// create or update it
		$id = $_POST['diqa_import_taggingrule_id'];
		if ($id == '') {
			$entry = new TaggingRule();
			$entry->rule_class = $attribute;
			$entry->crawled_property = $crawledProperty;
			$entry->type = $type;
			$entry->return_value = $returnValue;
			$entry->priority = $priority;
				
			$entry->save ();
			
			$pos = 0;
			foreach($parameters as $param) {
				
				if ($param == '') {
					continue;
				}
				$trParam = new TaggingRuleParameter();
				$trParam->parameter = $param;
				$trParam->pos = $pos;
				$entry->getParameters()->save($trParam);
			}
			
		} else {
			$entry = TaggingRule::where('id', $id)->get()->first();
			$entry->rule_class = $attribute;
			$entry->crawled_property = $crawledProperty;
			$entry->type = $type;
			$entry->return_value = $returnValue;
			$entry->priority = $priority;
				
			$entry->save();
			
			$entry->getParameters()->delete();
			
			$pos = 0;
			foreach($parameters as $param) {
			
				if ($param == '') {
					continue;
				}
				$trParam = new TaggingRuleParameter();
				$trParam->parameter = $param;
				$trParam->pos = $pos;
				$entry->getParameters()->save($trParam);
			}
		}
		
		self::addHintToRefreshSemanticData();
	}
	
	/**
	 * Remove tagging rule
	 *
	 * @param id
	 */
	private function doRemoveTaggingRule($id) {
	
		$entry = TaggingRule::where('id', $id);
		$entry->get()->first()->getParameters()->delete(); // FIXME: could be obsolete when using foreign keys
		$entry->delete();
		
		self::addHintToRefreshSemanticData();
	}

	/**
	 * Edit tagging rule
	 *
	 * @param id
	 * @param string (out) html
	 */
	private function doEditTaggingRule($id, & $html) {
	
		$taggingProperties = $this->getTaggingProperties ();

		$entry = TaggingRule::where('id', $id)->get()->first();
		$html .= $this->blade->view ()->make ( "specials.tagging.import-special-taggingrule-form",
				[	'taggingRule' => $entry,
					'taggingProperties' => $taggingProperties,
					'edit' => true ] )->render ();
		
		self::addHintToRefreshSemanticData();
	}
	
	/**
	 * Reads the tagging properties. Either from configuration or semantic model.
	 *
	 */
	 private function getTaggingProperties() {
		global $dimgAttributes;
		
		if (!isset($dimgAttributes) || count($dimgAttributes) == 0) {
			$taggingProperties = QueryUtils::executeQuery('[[Property:+]]');
			$taggingProperties = array_map(function($e) {
				return $e->getTitle()->getText();
			}, $taggingProperties);
		} else {
			$taggingProperties = $dimgAttributes;
		}
		
		global $wgContLang;
		$taggingProperties[] = $wgContLang->getNsText(NS_CATEGORY);
		return $taggingProperties;
		
	}

	
	/**
	 * Test the rule.
	 *
	 * @param id Rule-ID
	 * @param article Article title (+ PageID) of the testpage
	 * @param pageId Wiki article title of the testpage
	 * @param testedRule tested rule
	 * @param string (out) html
	 */
	private function doTestTaggingRule($id, $article, $pageId, $testedRule, & $html) {
	
		$taggingRule = TaggingRule::where('id', $id)->get()->first();
		$taggingProperties = $this->getTaggingProperties ();
		
		// if article is actually the page-ID
		$mwTitle = Title::newFromText($article);
		if (!is_null($mwTitle) && $mwTitle->exists()) {
			$pageId = $mwTitle->getPrefixedText();
		}
		
		// if page-ID was not given and could not be determined from article, stop here
		if ($pageId == '') {
			$html .= $this->blade->view ()->make ( "specials.tagging.import-special-test-taggingrule-form",
					[	'taggingRule' => $taggingRule,
						'taggingProperties' => $taggingProperties,
						'article' => '',
						'pageid' => '',
						'output' => '', 
						'edit' => true,
						'anyRuleApplied' => false ] )->render ();
			return;
		}
		
		// get rules and articles parameters
		$mwTitle = Title::newFromText($pageId);
		if (!$mwTitle->exists()) {
			throw new Exception("Article does not exist : {$mwTitle->getPrefixedText()}");
		}
		
		$rules = TaggingRule::where('rule_class', $taggingRule->getRuleClass())
		->orderBy('priority')
		->get();
		
		$revision = Revision::newFromTitle ( $mwTitle );
		$wikiPageContent = $revision->getContent ( Revision::RAW )->serialize ();
		$templateEditor = new TemplateEditor($wikiPageContent);
		$params = $templateEditor->getTemplateParams('DIQACrawlerDocument');
		
		// replace rules with tested rule (can be modified by the user)
		$replacedRules = [];
		for($i = 0; $i < count($rules); $i++) {
			if ($rules[$i]->id == $testedRule->id) {
				$replacedRules[] = $testedRule;
				
			} else {
				$replacedRules[] = $rules[$i];
			}
		}
		usort($replacedRules, function($rule1, $rule2) {
			return $rule1->priority - $rule2->priority;
		});
		
		// apply rules
		$output = '';
		$info = null;
		foreach($replacedRules as $rule) {
				
			$lastRule = $rule;
			TaggingRuleParserFunction::applyRule ( $rule, $params, $output, $info );
				
			// stop if the rule was effective
			if ($output != '') {
				break;
			}
		}
		
		$currentRuleApplied = $lastRule->id == $taggingRule->id;
		$anyRuleApplied = $output != '';
		
		
		$html .= $this->blade->view ()->make ( "specials.tagging.import-special-test-taggingrule-form",
				[	'taggingRule' => $testedRule->id != 0 ? $testedRule : $taggingRule,
					'taggingProperties' => $taggingProperties,
					'article' => $article,
					'pageid' => $pageId,
					'output' => $output,
					'currentRuleApplied' => $currentRuleApplied,
					'anyRuleApplied' => $anyRuleApplied,
					'lastRule' => $lastRule,
					'edit' => true,
					'ruleInfo' => $info ] )
		->render ();
	}
	
	/**
	 * Copy tagging rule
	 *
	 * @param id
	 * @param string (out) html
	 */
	private function doCopyTaggingRule($id, & $html) {
	
		$entry = TaggingRule::where('id', $id)->get()->first();
		if (is_null($entry)) {
			throw new Exception("Tagging rule with ID: $id does not exist", self::ERROR_INTERNAL);
		}
		$copy = TaggingRule::copy($entry);
		return $copy;
	}
	
	
	/**
	 * Shows the default content of the DIQAimport special page.
	 *
	 * @param string (out) $html
	 */
	private function showDefaultContent(& $html) {
		
		$taggingRules = TaggingRule::where('id', '>', 0)
			->orderBy('rule_class')
			->orderBy('priority')
			->get();
		
		$ruleClasses = [];
		
		foreach($taggingRules as $rule) {
			$ruleClasses[$rule->getRuleClass()][] = $rule;
		}
		
		$taggingProperties = $this->getTaggingProperties ();
		
		$html = $this->blade->view ()->make ( "specials.tagging.import-special-page",
				[
					'ruleClasses' => $ruleClasses,
					'taggingProperties' => $taggingProperties,
					'needsRefresh' => self::needsHintToRefreshSemanticData(),
		] )->render ();
	}
	
	/**
	 * Exports all rules as XML
	 */
	private function doExportTaggingRules() {
		
		$xml = new \SimpleXMLElement('<taggingRules/>');
		
		$rules = TaggingRule::all();
		foreach ($rules as $rule) {
			$ruleElement = $xml->addChild('rule');
			$ruleElement->addChild('rule_class', $rule->getRuleClass());
			$ruleElement->addChild('crawled_property', $rule->getCrawledProperty());
			$parameters = $ruleElement->addChild('parameters');
			foreach($rule->getParameters()->get() as $param) {
				$parameters->addChild('param', $param->getParameter());
			}
			$ruleElement->addChild('type', $rule->getType());
			$ruleElement->addChild('priority', $rule->getPriority());
			$ruleElement->addChild('return_value', $rule->getReturnValue());
		
		}
		
		// pretty-print
		$dom = new \DOMDocument("1.0");
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$xml = $dom->saveXML();
		
		return $xml;
		
	}
	
	/**
	 * Imports tagging rules from xml file.
	 *
	 * @param file The file location
	 */
	public static function doImportTaggingRules($file) {
		$xml = file_get_contents($file);
		$xmlDoc = @simplexml_load_string ( $xml );
		if ($xmlDoc === false) {
			throw new Exception("invalid XML document");
		}
		$rules = $xmlDoc->xpath('//rule');
		
		TaggingRule::where('id', '>=', 0)->delete();
		TaggingRuleParameter::where('id', '>=', 0)->delete();
		
		foreach($rules as $rule) {
			$entry = new TaggingRule();
			$entry->rule_class = $rule->rule_class[0];
			$entry->crawled_property = $rule->crawled_property[0];
			$entry->type = $rule->type[0];
			$entry->priority = $rule->priority[0];
			$entry->return_value = $rule->return_value[0];
			$entry->save();
			
			$pos = 0;
			//echo print_r($rule->parameters,true);die();
			foreach($rule->parameters->param as $param) {
				$paramText = (string) $param;
				if ($paramText == '') {
					continue;
				}
				$trParam = new TaggingRuleParameter();
				$trParam->parameter = $paramText;
				$trParam->pos = $pos;
				$entry->getParameters()->save($trParam);
				$pos++;
			}
		}
		
		self::addHintToRefreshSemanticData();
	}
	
	public static function addHintToRefreshSemanticData() {
		$cache = \ObjectCache::getInstance(CACHE_DB);
		$cache->set('DIQA.Import.needsSemanticDataRefresh', "true");
	}
	
	/**
	 * Checks if refresh hint is needed.
	 *
	 * @return boolean
	 */
	public static function needsHintToRefreshSemanticData() {
		$cache = \ObjectCache::getInstance(CACHE_DB);
		return $cache->get('DIQA.Import.needsSemanticDataRefresh') === "true";
	}
	
	/**
	 * Removes refresh hint.
	 *
	 */
	public static function removeHintToRefreshSemanticData() {
		$cache = \ObjectCache::getInstance(CACHE_DB);
		$cache->delete('DIQA.Import.needsSemanticDataRefresh');
	}
}
