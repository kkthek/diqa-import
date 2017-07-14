<?php
namespace DIQA\Import;

use Parser;

use DIQA\Import\Models\TaggingRule;
use DIQA\Util\TemplateEditor;
use DIQA\Util\QueryUtils;

/**
 * #chooseTaggingValue applies tagging rules in wikitext
 * 
 * @author Kai
 *
 */
class TaggingRuleParserFunction {
	
	private static $text;
	private static $synonyms = null;
	
	/**
	 * Render the output of {{#chooseTaggingValue: <attribute-title> | sub-pattern (optional) }}.
	 */
	static function chooseTaggingValue(Parser &$parser, $attribute = '') {
		$parser->disableCache ();
		
		$rules = TaggingRule::where('rule_class', trim($attribute))
					->orderBy('priority')
					->get();
		
		$templateEditor = new TemplateEditor(self::$text);
		$params = $templateEditor->getTemplateParams('DIQACrawlerDocument');
		
		$output = '';
		$info = null;
		foreach($rules as $rule) {
			
			self::applyRule ( $rule, $params, $output, $info );
			
			// stop if the rule was effective
			if ($output != '') {
				break;
			}
		}
		
		return array($output, 'noparse' => false);
	}
	
	/**
	 * Applies a rule.
	 * 
	 * @param $rule The rule
	 * @param params template key/value pairs
	 * @param output (out) Output of rule
	 * @param info (out) Information about rule applied
	 */
	 public static function applyRule($rule, $params, & $output, & $info) {
		if (is_null($params)) {
			return;
		}
		
		if ($rule->getCrawledProperty() != '' && !array_key_exists($rule->getCrawledProperty(), $params)) {
			return;
		}
		
		$crawledPropertiesToTest = $rule->getCrawledProperty() != '' ? [ $rule->getCrawledProperty() ] : array_keys($params);
		
		foreach($crawledPropertiesToTest as $crawledPropertyToTest) {
		
			switch($rule->getType()) {
				
				case "metadata":
					$output = $params[$crawledPropertyToTest];
					self::applySynonyms($rule, $output, $info);
					break;
					
				case "regex":
				case "regex-path":
					$value = $params[$crawledPropertyToTest];
					
					foreach($rule->getParameters()->get() as $param) {
						$pattern = str_replace('/', '\/', $param->getParameter());
						$matches = [];
						$num = preg_match_all("/$pattern/", $value, $matches);
						
						if ($num > 0 && $rule->getReturnValue() != '') {
							
							$output = $rule->getReturnValue();
							$output = preg_replace_callback('/\$(\d+)/', function($rep) use ($matches) { 
								return isset($rep[1]) ? reset($matches[$rep[1]]) : '';
							}, $output);
								
							self::applySynonyms($rule, $output, $info);
							
						} else if ($num > 0) {
							$output = isset($matches[1]) ? trim(reset($matches[1])) : reset($matches[0]);
							
							self::applySynonyms($rule, $output, $info);
						} else {
							$output = '';
						}
						
						if ($output != '') {
							break;
						}
					}
					
					break;
					
				default: 
					// do nothing
					break;
			}
			if ($output != '') {
				break;
			}
		}
		
	}

	static function applySynonyms($rule, & $output, &$info) {
		global $dimgAttributesToResolvePageID;
		
		$info['synonymApplied'] = false;
		if (!in_array($rule->getRuleClass(), $dimgAttributesToResolvePageID)) {
			return;
		}
		// check for synonyms and return page-ID
		// and some information about the synonym
		self::readSynonyms();
		if (array_key_exists($output, self::$synonyms)) {
			$original = $output;
			$output = self::$synonyms[$original];
			$info['synonymApplied'] = true;
			$info['original'] = $original;
			$info['outputTitle'] = \Title::newFromText($output);
			$info['outputTitleText'] = self::$synonyms['__'.$output];
		}
		
		
	}
	
	static function parserAfterStrip(Parser &$parser, & $text, $state) {
		self::$text = $text;
	}
	
	/**
	 * Reads all synonym/title-annotations once.
	 * Returns a list of synonym/title => page-ID
	 * 
	 * @return multitype:string
	 */
	static function readSynonyms() {
		
		if (!is_null(self::$synonyms)) {
			return self::$synonyms;
		}	
		
		$synonymProperty = wfMessage('diqa-import-tagging-synonyms')->text();
		$printout = new \SMWPrintRequest ( \SMWPrintRequest::PRINT_PROP, "$synonymProperty", 
				\SMWPropertyValue::makeUserProperty ( $synonymProperty ) );
		
		global $fsgTitleProperty;
		$printout_title = new \SMWPrintRequest ( \SMWPrintRequest::PRINT_PROP, "$fsgTitleProperty", 
				\SMWPropertyValue::makeUserProperty ( $fsgTitleProperty ) );
		
		$query_result = QueryUtils::executeBasicQuery ( "[[$synonymProperty::+]]", [
				$printout_title, $printout ], [ 'limit' => 10000	] );
		
		$results = [];
		while ( $res = $query_result->getNext () ) {
				
			$pageID = $res [0]->getNextText ( SMW_OUTPUT_WIKI );
			$mwTitle = \Title::newFromText ( $pageID );
			$title = $res [1]->getNextText ( SMW_OUTPUT_WIKI );
			
			$results[$title] = $mwTitle->getPrefixedText();
			$results['__'.$mwTitle->getPrefixedText()] =  $title;
			
			while ($pageTitle = $res [2]->getNextText ( SMW_OUTPUT_WIKI )) {
				$results [$pageTitle] = $mwTitle->getPrefixedText();
			}
			
				
		}
		
		self::$synonyms = $results;
		return $results;
	}
}