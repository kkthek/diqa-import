<?php
namespace DIQA\Import;

use DIQA\Util\TemplateEditor;

class MetadataIndexer {
	
	/**
	 * Add the metadata directly from template parameters.
	 * 
	 * @param string $text
	 * @param array $doc
	 */
	public static function addExtractedMetadata(& $text, & $doc) {
		
		global $wgDIQAImportUseAllMetadata;
		if ($wgDIQAImportUseAllMetadata !== true) {
			return;
		}
		
		$templateEditor = new TemplateEditor($text);
		$params = $templateEditor->getTemplateParams('DIQACrawlerDocument');
		
		foreach($params as $attribute => $value) {
			
			if ($value == '') {
				continue;
			}
			
			$sanitizedName = self::sanitzeAttributeName($attribute);
			$propXSD = "smwh_{$sanitizedName}_xsdvalue_t";
			$doc[$propXSD] = $value;
			
			if (!in_array($propXSD, $doc['smwh_attributes'])) {
				$doc['smwh_attributes'][] = $propXSD;
			}
		}
		
	}
	
	private static function sanitzeAttributeName($attribute) {
		$attribute = str_replace(" ", "_", $attribute);
		$attribute = str_replace(['\\','/',':','*','?','"','<','>','|','-'], '_', $attribute);
		$attribute = preg_replace("/_{2,}/", "_", $attribute);
		return $attribute;
	}
}