<tr ruleID="{{$taggingRule->id}}">
<td>{{$taggingRule->getCrawledProperty()}}</td>
<td>{{wfMessage('diqa-import-tagging-type-'.$taggingRule->getType())->text()}}</td>
<td>
@foreach($taggingRule->getParameters()->get() as $param)
<br>{{$param->getParameter()}}
@endforeach
</td>
<td>{{$taggingRule->getTitleForReturnValue()}}</td>
<td class="diqa-import-action-column">
<div>
@include('specials.general.import-special-command', ['command' => 'diqa-import-edit-rule', 'img' => 'edit.png', 'id' => $taggingRule->id, 'page' => 'Special:DIQAtagging' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-copy-rule', 'img' => 'copy.png', 'id' => $taggingRule->id, 'page' => 'Special:DIQAtagging' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-test-rule', 'img' => 'test.png', 'id' => $taggingRule->id, 'page' => 'Special:DIQAtagging' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-remove-rule', 'img' => 'remove.png', 'id' => $taggingRule->id, 'page' => 'Special:DIQAtagging' ])
@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-sortup-rule', 'img' => 'sort-up.png'])
@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-sortdown-rule', 'img' => 'sort-down.png'])
</div>
</td>
</tr>