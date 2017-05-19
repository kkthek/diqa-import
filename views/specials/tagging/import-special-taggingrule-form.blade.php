<?php
use DIQA\Import\Models\TaggingRule; 
?>

@if (!$edit)
<div class="diqa-import-add-link">
<a onclick="javascript: $('#add-tagging-rule').toggle();">
[Hinzufügen]
</a>
</div>
@endif
<div id="add-tagging-rule" class="diqa-import-form" style="{{$edit ? 'display: block' : 'display: none'}}">
<form action="{{wfDIQAURL('Special:DIQAtagging')}}" method="post">
<table>

	<tr>
	<td>
	<span>{{wfMessage('diqa-import-tagging-attribute')->text()}}</span>
	</td>
	<td>
	<select name="diqa_taggingrule_attribute">
		@foreach($taggingProperties as $p)
			<option value="{{$p}}" {{isset($taggingRule) && $taggingRule->getRuleClass() == $p ? 'selected=true' : ''}}>
				{{$p}}
			</option>
		@endforeach
	</select>
	</td>
	</tr>
	
	<tr>
	<td>
	<span>{{wfMessage('diqa-import-tagging-crawledProperty')->text()}}</span>
	</td>
	<td>
	<input type="text" size="60" name="diqa_taggingrule_crawledProperty" value="{{isset($taggingRule) ? $taggingRule->getCrawledProperty() : ''}}"/>
	<br>({{wfMessage('diqa-import-crawled-property-hint')->text()}})
	</td>
	</tr>
	
	<tr>
	<td>
	<span>{{wfMessage('diqa-import-tagging-type')->text()}}</span>
	</td>
	<td>
	<select name="diqa_taggingrule_type">
		@foreach(TaggingRule::$TAGGING_TYPES as $type)
			<option value="{{$type}}" {{isset($taggingRule) && $taggingRule->getType() == $type ? 'selected=true' : ''}}>
				{{wfMessage('diqa-import-tagging-type-'.$type)->text()}}
			</option>
		@endforeach
	</select>
	</td>
	</tr>
	
	<tr id="diqa-import-tagging-constraint" style="{{$taggingRule->type == 'regex' ? '' : 'display:none'}};">
	<td>
	<span>{{wfMessage('diqa-import-tagging-constraint')->text()}}</span>
	</td>
	<td>
	<input type="text" size="60" name="diqa_taggingrule_parameters" value="{{isset($taggingRule) ? $taggingRule->getParameters() : ''}}"/>
	@include('specials.general.import-special-js-button', ['command' => 'diqa-import-open-folder-picker' ])
	</td>
	</tr>
	
	<tr id="diqa-import-tagging-returnvalue" style="{{$taggingRule->type == 'regex' ? '' : 'display:none'}};">
	<td>
	<span>{{wfMessage('diqa-import-tagging-return-value')->text()}}</span>
	</td>
	<td>
	<input type="text" size="60" name="diqa_taggingrule_returnvalue" value="{{isset($taggingRule) ? $taggingRule->getReturnValue() : ''}}"/>
	 <br>({{wfMessage('diqa-import-returnvalue-hint')->text()}})
	</td>
	</tr>
	
	<tr>
	<td>
	<span>{{wfMessage('diqa-import-tagging-priority')->text()}}</span>
	</td>
	<td>
	<select name="diqa_taggingrule_priority">
	@foreach([0,1,2,3,4,5,6,7,8,9] as $prio)
		<option value="{{$prio}}" {{isset($taggingRule) && $taggingRule->getPriority() == $prio ? 'selected=true' : ''}}>
			{{$prio}}
		</option>
	@endforeach
	
	</select>
	</td>
	</tr>
	
	<tr>
	<td>
	<input type="submit" value="{{wfMessage('diqa-save-button')->text()}}" name="add-import-taggingrule" />
	@if (isset($taggingRule))
	<input type="submit" value="{{wfMessage('diqa-cancel-button')->text()}}" name="cancel-import-taggingrule" />
	@endif
	</td>
	</tr>
	
	<input type="hidden" value="{{isset($taggingRule) ? $taggingRule->id : ''}}" name="diqa_import_taggingrule_id"/>
	<input type="hidden" value="{{$_SERVER['REQUEST_URI']}}" name="diqa_import_returnurl"/>
</table>

</form>
</div>