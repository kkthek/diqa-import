<?php
namespace DIQA\Import\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a tagging rule. Tagging rules are used to 
 * "connect" the crawled data to semantic properties/categories.
 * 
 * @author Kai
 *
 */
class TaggingRule extends Model {
	
	public static $TAGGING_TYPES = ['metadata', 'regex'];
	
	protected $table = 'diqa_imports_taggingrules';
	public $timestamps = false;
	
	public static function copy(TaggingRule $rule) {
		$copy = new TaggingRule();
		$copy->rule_class = $rule->getRuleClass();
		$copy->crawled_property = $rule->getCrawledProperty();
		$copy->parameters = $rule->getParameters();
		$copy->type = $rule->getType();
		$copy->priority = $rule->getPriority();
		$copy->return_value = $rule->getReturnValue();
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
	 * @return string
	 */
	public function getParameters() {
		return $this->parameters;
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
	
	public function toString() {
		switch($this->type) {
			case 'metadata':
				return "ID: {$this->id}, Rule-Class: '{$this->rule_class}', Crawled property: '{$this->crawled_property}',".
						"Type: '{$this->type}', Priority: {$this->priority}";
				break;
			case 'regex':
				return "ID: {$this->id}, Rule-Class: '{$this->rule_class}', Crawled property: '{$this->crawled_property}',".
					"Type: '{$this->type}', Parameter: '{$this->parameters}', ".
					"Return value: '{$this->return_value}', Priority: {$this->priority}";
				break;
		}
		return 'unknown tagging rule type';
	}
	
}