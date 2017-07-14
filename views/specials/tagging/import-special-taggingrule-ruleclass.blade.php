<div class="diqa-taggingrule-container">
	<div class="diqa-taggingrule-header" attribute="{{$ruleClass}}">
	<span>{{$ruleClass}}</span>
	@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-add-link', 'img' => 'add.png' ])
	@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-save-rule-order', 'img' => 'save.png', 'style' => 'display:none;' ])
	</div>
	
	<div style="display:none" class="diqa-taggingrule-table">
	@include('specials.tagging.import-special-taggingrule-table', ['taggingRules' => $taggingRules])
	</div>
</div>