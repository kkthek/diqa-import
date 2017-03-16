<?php
namespace DIQA\Import;

use Parser;

use DIQA\Import\Models\TaggingRule;
use DIQA\Util\TemplateEditor;

/**
 * #chooseTaggingValue applies tagging rules in wikitext
 * 
 * @author Kai
 *
 */
class TaggingRuleParserFunction {
	
	private static $text;
	
	/**
	 * Render the output of {{#chooseTaggingValue: <attribute-title> | sub-pattern (optional) }}.
	 */
	static function chooseTaggingValue(Parser &$parser, $attribute = '', $index = 1) {
		$parser->disableCache ();
		
		$rules = TaggingRule::where('rule_class', trim($attribute))
					->orderBy('priority')
					->get();
		
		$templateEditor = new TemplateEditor(self::$text);
		$params = $templateEditor->getTemplateParams('DIQACrawlerDocument');
		
		$output = '';
		foreach($rules as $rule) {
			
			self::applyRule ( $rule, $params, $output, $index );
			
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
	 public static function applyRule($rule, $params, & $output, $index = 1) {
		if (is_null($params) || !array_key_exists($rule->getCrawledProperty(), $params)) {
			return;
		}
		
		switch($rule->getType()) {
			
			case "metadata":
				$output = $params[$rule->getCrawledProperty()];
				break;
				
			case "regex":
				$value = $params[$rule->getCrawledProperty()];
				$pattern = $rule->getParameters();
				$pattern = str_replace('/', '\/', $pattern);
				$matches = [];
				$num = preg_match_all("/$pattern/", $value, $matches);
				
				if ($num > 0 && $rule->getReturnValue() != '') {
					$output = $rule->getReturnValue();
				} else {
					$output = isset($matches[$index]) ? reset($matches[$index]) : '';
				}
				
				break;
				
			default: 
				// do nothing
				break;
		}}

	
	static function parserAfterStrip(Parser &$parser, & $text, $state) {
		self::$text = $text;
	}
}