<div class="diqa-taggingrule-container">
	<div class="diqa-taggingrule-header" attribute="{{$ruleClass}}">
	<span>{{$ruleClass}}</span>
	@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-unfold-link', 'img' => 'sort-down.png' ])
	@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-fold-link', 'img' => 'sort-up.png', 'style' => 'display:none;' ])
	@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-save-rule-order', 'img' => 'save.png', 'style' => 'display:none;' ])
	</div>
	
	<div style="display:none" class="diqa-taggingrule-table" attribute="{{$ruleClass}}">
	@include('specials.tagging.import-special-taggingrule-table', ['taggingRules' => $taggingRules, 'attribute' => $ruleClass])
	</div>
</div>