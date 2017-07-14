<?php
namespace DIQA\Import\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a tagging rule parameter.
 * 
 * @author Kai
 *
 */
class TaggingRuleParameter extends Model {
		
	protected $table = 'diqa_imports_taggingrule_parameters';
	public $timestamps = false;
	
	/**
	 * Clones this TaggingRuleParameter (NOT in database) 
	 * @param TaggingRuleParameter $parameter
	 * @return \DIQA\Import\Models\TaggingRuleParameter
	 */
	public static function cloneParameter(TaggingRuleParameter $parameter) {
		$copy = new TaggingRuleParameter();
		$copy->parameter = $parameter->getParameter();
		$copy->pos = $parameter->getPos();
		return $copy;
	}
	
	/**
	 * Get parameter
	 * @return string
	 */
	public function getParameter() {
		return $this->parameter;
	}
	
	/**
	 * Get parameter position
	 * @return string
	 */
	public function getPos() {
		return $this->pos;
	}
	
	
	
	public function toString() {
		return "ID: {$this->id}, Parameter: '{$this->parameter}', Position: '{$this->pos}'";
	}
	
}