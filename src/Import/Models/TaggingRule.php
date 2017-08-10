<?php
namespace DIQA\Import\Models;

use Illuminate\Database\Eloquent\Model;
use SMW\StoreFactory;

/**
 * Represents a tagging rule. Tagging rules are used to 
 * "connect" the crawled data to semantic properties/categories.
 * 
 * @author Kai
 *
 */
class TaggingRule extends Model {
	
	public static $TAGGING_TYPES = ['metadata', 'regex', 'regex-path'];
	
	protected $table = 'diqa_imports_taggingrules';
	public $timestamps = false;
	
	/**
	 * Creates a copy of this rule in database.
	 * 
	 * @param TaggingRule $rule
	 * @return \DIQA\Import\Models\TaggingRule
	 */
	public static function copy(TaggingRule $rule) {
		$copy = new TaggingRule();
		$copy->rule_class = $rule->getRuleClass();
		$copy->crawled_property = $rule->getCrawledProperty();
		$copy->type = $rule->getType();
		$copy->priority = $rule->getPriority();
		$copy->return_value = $rule->getReturnValue();
		$copy->save();
		
		foreach($rule->getParameters()->get() as $parameter) {
			$copy->getParameters()->save(TaggingRuleParameter::cloneParameter($parameter));
		}
		return $copy;
	}
	/**
	 * Serves as an identification for the rule via #chooseTaggingValue.
	 * It *should* match the attribute title where it is used for.
	 * @return string
	 */
	public function getRuleClass() {
		return $this->rule_class;
	}
	
	/**
	 * The crawled property (=extracted via Apache Tika from the document)
	 * Actually one of the template parameters.
	 * @return string
	 */
	public function getCrawledProperty() {
		return $this->crawled_property;
	}
	
	/**
	 * Extra constraint for this rule. Semantics depends on the rule type.
	 * e.g. a regular expression
	 * @return array of TaggingRuleParameter
	 */
	public function getParameters() {
		return $this->hasMany('DIQA\Import\Models\TaggingRuleParameter', 'fk_taggingrule')->orderBy('pos');
	}
	
	/**
	 * The type of the rule. One of the values of $TAGGING_TYPES
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * The priority of the rule. smaller number means higher priority.
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}
	
	/**
	 * Returns a fixed value if the tagging rule applies
	 * @return string
	 */
	public function getReturnValue() {
		return $this->return_value;
	}
	
	/**
	 * Reads Title-property from article denoted by $this->return_value.
	 * If it does not exist, it just returns $this->return_value.
	 * 
	 * @return string
	 */
	public function getTitleForReturnValue() {
		
		if ($this->return_value == '') {
			return $this->return_value;
		}
		
		if ($this->return_value == '-all-') {
			return '-' . wfMessage('diqa-import-all-values')->text() . '-';
		}
		
		$store = StoreFactory::getStore ();
		$title = \SMWDIWikiPage::newFromText($this->return_value);
		
		if (is_null($title)) {
			return $this->return_value;
		}
		
		global $dimgTitleAttribute;
		$value = $store->getPropertyValues( $title,
				\SMWDIProperty::newFromUserLabel ( $dimgTitleAttribute ) ); 
		$value = reset($value);
		return $value !== false ? $value->getString() : $this->return_value;
	}
	
	/**
	 * Returns parameters as plain array.
	 * 
	 * @return array of string
	 */
	public function getParametersAsPlainArray() {
		
		$r=[];
		foreach($this->getParameters()->get() as $param) {
			$r[] = $param->getParameter();
		}
		
		return $r;
	}
	
	public function toString() {
		switch($this->type) {
			case 'metadata':
				return "ID: {$this->id}, Rule-Class: '{$this->rule_class}', Crawled property: '{$this->crawled_property}',".
						"Type: '{$this->type}', Priority: {$this->priority}";
				break;
			case 'regex':
			case 'regex-path':
				$paramsRegexs = getParametersAsPlainArray();
				return "ID: {$this->id}, Rule-Class: '{$this->rule_class}', Crawled property: '{$this->crawled_property}',".
					"Type: '{$this->type}', Parameters: [".implode(',', $paramsRegexs)."], ".
					"Return value: '{$this->return_value}', Priority: {$this->priority}";
				break;
		}
		return 'unknown tagging rule type';
	}
	
}

// Make sure deleting a tagging rule cascades to its parameters
TaggingRule::deleting(function($rule) { 
		$rule->getParameters()->delete();
});