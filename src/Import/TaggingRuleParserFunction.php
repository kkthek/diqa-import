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
		foreach($rules as $rule) {
			
			self::applyRule ( $rule, $params, $output );
			
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
	 * @param $rule
	 * @param params
	 * @param output (out)
	 */
	 public static function applyRule($rule, $params, & $output) {
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
					self::applySynonyms($rule, $output);
					break;
					
				case "regex":
					$value = $params[$crawledPropertyToTest];
					$pattern = $rule->getParameters();
					$pattern = str_replace('/', '\/', $pattern);
					$matches = [];
					$num = preg_match_all("/$pattern/", $value, $matches);
					
					if ($num > 0 && $rule->getReturnValue() != '') {
						
						$output = $rule->getReturnValue();
						$output = preg_replace_callback('/\$(\d+)/', function($rep) use ($matches) { 
							return isset($rep[1]) ? reset($matches[$rep[1]]) : '';
						}, $output);
							
						self::applySynonyms($rule, $output);
						
					} else if ($num > 0) {
						$output = isset($matches[1]) ? trim(reset($matches[1])) : reset($matches[0]);
						
						self::applySynonyms($rule, $output);
					} else {
						$output = '';
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

	static function applySynonyms($rule, & $output) {
		global $dimgAttributesToResolvePageID;
		
		if (!in_array($rule->getRuleClass(), $dimgAttributesToResolvePageID)) {
			return;
		}
		// check for synonyms and return page-ID
		self::readSynonyms();
		if (array_key_exists($output, self::$synonyms)) {
			$output = self::$synonyms[$output];
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
		
		$property = 'Synonyme'; //TODO: localize
		$printout = new \SMWPrintRequest ( \SMWPrintRequest::PRINT_PROP, "$property", 
				\SMWPropertyValue::makeUserProperty ( $property ) );
		
		global $fsgTitleProperty;
		$printout_title = new \SMWPrintRequest ( \SMWPrintRequest::PRINT_PROP, "$fsgTitleProperty", 
				\SMWPropertyValue::makeUserProperty ( $fsgTitleProperty ) );
		
		$query_result = QueryUtils::executeBasicQuery ( "[[$property::+]]", [
				$printout_title, $printout ], [ 'limit' => 10000	] );
		
		$results = [];
		while ( $res = $query_result->getNext () ) {
				
			$pageID = $res [0]->getNextText ( SMW_OUTPUT_WIKI );
			$mwTitle = \Title::newFromText ( $pageID );
			$title = $res [1]->getNextText ( SMW_OUTPUT_WIKI );
			
			$results[$title] = $mwTitle->getPrefixedText();
			
			while ($pageTitle = $res [2]->getNextText ( SMW_OUTPUT_WIKI )) {
				$results [$pageTitle] = $mwTitle->getPrefixedText();
			}
			
				
		}
		
		self::$synonyms = $results;
		return $results;
	}
}