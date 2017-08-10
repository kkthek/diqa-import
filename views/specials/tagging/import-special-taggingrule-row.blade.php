<tr ruleID="{{$taggingRule->id}}">
<td>{{$taggingRule->getCrawledProperty()}}</td>
<td>{{wfMessage('diqa-import-tagging-type-'.$taggingRule->getType())->text()}}</td>
<td>
<div style="word-break: break-all;">
@foreach($taggingRule->getParameters()->get() as $param)
<div style="margin-top: 5px">
	<a onclick="javascript: return;" style="cursor: text;color: inherit;text-decoration: none;" title="{{$param->getParameter()}}">
	{{wfDIQAShorten($param->getParameter())}}
	</a>
</div>
@endforeach
</div>
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