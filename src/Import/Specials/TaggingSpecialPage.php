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
		
		$wgOut->setPageTitle ( wfMessage ( 'diqa-import-title' )->text () );
		
		try {
			
			$html = '';
			$this->checkPriviledges();
			$this->dispatchRequest ($html);
					
			
		} catch(Exception $e) {
			$html = $this->blade->view ()->make ( "specials.general.import-special-error", 
					[	'msg' => $e->getMessage() ] )->render ();
		}
		$wgOut->addHTML ( $html );
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
			$this->doCopyTaggingRule ($id, $html );
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
			$this->doImportTaggingRules ( $file );

			
		}
		
		
		
		$this->showDefaultContent($html);
	}
		
	private function doAddOrUpdateTaggingRule($attribute, $crawledProperty, $type, $parameters, $returnValue, $priority) {
			
		// validate entry
		if (!(is_numeric($priority) && floor($priority) == $priority)) {
			throw new Exception("Priority must be numeric: $priority", self::ERROR_NOT_NUMERIC);
		}
	
		if ($type == 'regex' && @preg_match("/$constraint/", null) === false) {
			throw new Exception("Regex is wrong: $constraint", self::ERROR_WRONG_REGEX);
		}
	
		// create or update it
		$id = $_POST['diqa_import_taggingrule_id'];
		if ($id == '') {
			$entry = new TaggingRule();
			$entry->rule_class = $attribute;
			$entry->crawled_property = $crawledProperty;
			$entry->type = $type;
			$entry->parameters = $parameters;
			$entry->return_value = $returnValue;
			$entry->priority = $priority;
				
			$entry->save ();
		} else {
			$entry = TaggingRule::where('id', $id)->get()->first();
			$entry->rule_class = $attribute;
			$entry->crawled_property = $crawledProperty;
			$entry->type = $type;
			$entry->parameters = $parameters;
			$entry->return_value = $returnValue;
			$entry->priority = $priority;
				
			$entry->save();
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
	
		$entry = TaggingRule::where('id', $id)->get()->first();
		$html .= $this->blade->view ()->make ( "specials.tagging.import-special-taggingrule-form",
				[	'taggingRule' => $entry,
					'edit' => true ] )->render ();
		
		self::addHintToRefreshSemanticData();
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
		if ($pageId == '') {
			$html .= $this->blade->view ()->make ( "specials.tagging.import-special-test-taggingrule-form",
					[	'taggingRule' => $taggingRule, 
						'article' => '',
						'pageid' => '',
						'output' => '', 
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
		foreach($replacedRules as $rule) {
				
			$lastRule = $rule;
			TaggingRuleParserFunction::applyRule ( $rule, $params, $output );
				
			// stop if the rule was effective
			if ($output != '') {
				break;
			}
		}
		
		$currentRuleApplied = $lastRule->id == $taggingRule->id;
		$anyRuleApplied = $output != '';
		$html .= $this->blade->view ()->make ( "specials.tagging.import-special-test-taggingrule-form",
				[	'taggingRule' => $testedRule->id != 0 ? $testedRule : $taggingRule, 
					'article' => $article,
					'pageid' => $pageId,
					'output' => $output, 
					'currentRuleApplied' => $currentRuleApplied,
					'anyRuleApplied' => $anyRuleApplied,
					'lastRule' => $lastRule ] )
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
		$copy->save();
		
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
		
		$html = $this->blade->view ()->make ( "specials.tagging.import-special-page",
				[
					'taggingRules' => $taggingRules,
					
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
			$ruleElement->addChild('parameters', $rule->getParameters());
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
	private function doImportTaggingRules($file) {
		$xml = file_get_contents($file);
		$xmlDoc = @simplexml_load_string ( $xml );
		if ($xmlDoc === false) {
			throw new Exception("invalid XML document");
		}
		$rules = $xmlDoc->xpath('//rule');
		foreach($rules as $rule) {
			$entry = new TaggingRule();
			$entry->rule_class = $rule->rule_class[0];
			$entry->crawled_property = $rule->crawled_property[0];
			$entry->parameters = $rule->parameters[0];
			$entry->type = $rule->type[0];
			$entry->priority = $rule->priority[0];
			$entry->return_value = $rule->return_value[0];
			$entry->save();
		}
	}
	
	public static function addHintToRefreshSemanticData() {
		global $IP;
		@touch("$IP/images/.diqa-import-needs-refresh");
	}
	
	
}