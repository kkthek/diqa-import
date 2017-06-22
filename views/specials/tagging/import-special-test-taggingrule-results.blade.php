@if($anyRuleApplied)
<div>
	<h1>{{wfMessage('diqa-import-test-result')->text()}}</h1>
	<div style="margin-top: 20px">
		<div>{{wfMessage('diqa-import-rule-applied')->text()}}:
			{{$currentRuleApplied ? wfMessage('diqa-import-yes')->text() :
			wfMessage('diqa-import-no')->text() }}</div>
	@if(!$currentRuleApplied)

		<!-- Andere Regel greift -->
		<div>{{wfMessage('diqa-import-instead-rule-applied')->text()}}:
		<div style="margin-left: 20px">
			<div>Id: {{$lastRule->id}}</div>
			<div>Attribut: {{$lastRule->getRuleClass()}}</div>
			<div>Crawled property: {{$lastRule->getCrawledProperty()}}</div>
			<div>Parameter: {{$lastRule->getParameters()}}</div>
			<div>Type: {{$lastRule->getType()}}</div>
			
			@include('specials.general.import-special-command-newtab', ['command' => 'diqa-import-edit-rule', 'id' => $lastRule->id, 'page' => 'Special:DIQAtagging' ])</div>
		</div>
		<div>{{wfMessage('diqa-import-rule-output')->text()}}: '{{$output}}'</div>
		<br>
		<div>{{wfMessage('diqa-import-tagging-synonym-used')->text()}}:
			{{isset($ruleInfo['synonymApplied']) && $ruleInfo['synonymApplied'] == true ? 'ja' : 'nein'}}</div>
		@if (isset($ruleInfo['synonymApplied']) && $ruleInfo['synonymApplied'] == true)
		<div>{{wfMessage('diqa-import-tagging-synonym')->text()}}:
			{{$ruleInfo['original']}}</div>
		<div>{{wfMessage('diqa-import-tagging-translated-to')->text()}}:
			'{{$ruleInfo['outputTitle']->getPrefixedText()}}'
			({{$ruleInfo['outputTitleText']}})</div>
		@endif 
	
	@else

		<!-- Getestete Regel greift -->
		<div>{{wfMessage('diqa-import-rule-output')->text()}}: '{{$output}}'</div>
		<br>
		<div>{{wfMessage('diqa-import-tagging-synonym-used')->text()}}:
			{{isset($ruleInfo['synonymApplied']) && $ruleInfo['synonymApplied']
			== true ? 'ja' : 'nein'}}</div>
		@if (isset($ruleInfo['synonymApplied']) && $ruleInfo['synonymApplied'] == true)
		<div>{{wfMessage('diqa-import-tagging-synonym')->text()}}:
			{{$ruleInfo['original']}}</div>
		<div>{{wfMessage('diqa-import-tagging-translated-to')->text()}}:
			'{{$ruleInfo['outputTitle']->getPrefixedText()}}'
			({{$ruleInfo['outputTitleText']}})</div>
		@endif
	</div>
</div>
	@endif
	
@else
<!-- Keine Regel greift -->
{{wfMessage('diqa-import-no-rule-applied')->text()}} @endif
