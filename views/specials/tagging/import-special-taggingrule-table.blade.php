<div class="diqa-import-table">
<table>
<tr>
<th>{{wfMessage('diqa-import-tagging-attribute')->text()}}</th>
<th>{{wfMessage('diqa-import-tagging-crawledProperty')->text()}}</th>
<th>{{wfMessage('diqa-import-tagging-type')->text()}}</th>
<th>{{wfMessage('diqa-import-tagging-constraint')->text()}}</th>
<th>{{wfMessage('diqa-import-tagging-return-value')->text()}}</th>
<th>{{wfMessage('diqa-import-tagging-priority')->text()}}</th>
<th></th>
</tr>
@foreach($taggingRules as $taggingRule)
	@include('specials.tagging.import-special-taggingrule-row', [ 'taggingRule' => $taggingRule ])
@endforeach

</table>
</div>