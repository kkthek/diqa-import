<div class="diqa-import-table">

<table>
<tr>
<th>{{wfMessage('diqa-import-tagging-crawledProperty')->text()}}</th>
<th>{{wfMessage('diqa-import-tagging-type')->text()}}</th>
<th>{{wfMessage('diqa-import-tagging-constraint')->text()}}</th>
<th>{{wfMessage('diqa-import-tagging-return-value')->text()}}</th>
<th></th>
</tr>

@foreach($taggingRules as $taggingRule)
	@include('specials.tagging.import-special-taggingrule-row', [ 'taggingRule' => $taggingRule ])
@endforeach

<tr>
<td colspan="5">
@include('specials.general.import-special-command', ['command' => 'diqa-import-add-new-rule', 
'img' => 'add.png', 'page' => 'Special:DIQAtagging', 'param' => $attribute, 'text' => 'Regel hinzufügen' ])
</td>
</table>
</div>