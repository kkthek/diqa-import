<tr>
<td>{{$taggingRule->getRuleClass()}}</td>
<td>{{$taggingRule->getCrawledProperty()}}</td>
<td>{{$taggingRule->getType()}}</td>
<td>{{str_replace('\s',' ',$taggingRule->getParameters())}}</td>
<td>{{$taggingRule->getReturnValue()}}</td>
<td>{{$taggingRule->getPriority()}}</td>
<td>
@include('specials.general.import-special-command', ['command' => 'diqa-import-remove-rule', 'id' => $taggingRule->id, 'page' => 'Special:DIQAtagging' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-edit-rule', 'id' => $taggingRule->id, 'page' => 'Special:DIQAtagging' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-copy-rule', 'id' => $taggingRule->id, 'page' => 'Special:DIQAtagging' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-test-rule', 'id' => $taggingRule->id, 'page' => 'Special:DIQAtagging' ])
</td>
</tr>