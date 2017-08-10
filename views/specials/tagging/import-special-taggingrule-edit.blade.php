<?php
use DIQA\Import\Models\TaggingRule; 
?>

<table>

	<tr>
	<td>
	<span>{{wfMessage('diqa-import-tagging-attribute')->text()}}</span>
	</td>
	<td>
	<select disabled="disabled" name="diqa_taggingrule_attribute">
		@foreach($taggingProperties as $p)
			<?php 
				
				if(isset($taggingRule)) {
					$isSelected = $taggingRule->getRuleClass() == $p;
				} else if (isset($attribute)) {
					$isSelected = $attribute == $p;
				} 
			?>
			<option value="{{$p}}" {{$isSelected ? 'selected=true' : ''}}>
				{{$p}}
			</option>
		@endforeach
	</select>
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
	
	
	
	<tr id="diqa-import-tagging-returnvalue" style="{{isset($taggingRule) && $taggingRule->type == 'regex' ? '' : 'display:none'}};">
	<td>
	<span>{{wfMessage('diqa-import-tagging-return-value')->text()}}</span>
	</td>
	<td>
	<select  name="diqa_taggingrule_returnvalue">
	<option selected="true" value="{{isset($taggingRule) ? $taggingRule->getReturnValue() : ''}}">{{isset($taggingRule) ? $taggingRule->getTitleForReturnValue() : ''}}</option>
	</select>
	 <br><span id="diqa-import-return-value-hint">({{wfMessage('diqa-import-returnvalue-hint')->text()}})</span>
	</td>
	</tr>
	
	<tr id="diqa-import-tagging-crawledProperty" style="{{isset($taggingRule) && $taggingRule->type != 'regex-path' ? '' : 'display:none'}};">
	<td>
	<span>{{wfMessage('diqa-import-tagging-crawledProperty')->text()}}</span>
	</td>
	<td>
	<input type="text"  name="diqa_taggingrule_crawledProperty" value="{{isset($taggingRule) ? $taggingRule->getCrawledProperty() : ''}}"/>
	<br>({{wfMessage('diqa-import-crawled-property-hint')->text()}})
	</td>
	</tr>
	
	<tr id="diqa-import-tagging-constraint" style="{{isset($taggingRule) && $taggingRule->getType() == 'regex' ? '' : 'display:none'}};">
	<td>
	<span id="diqa-import-tagging-constraint-label">{{wfMessage('diqa-import-tagging-constraint')->text()}}</span>
	</td>
	<td>
	@if(isset($taggingRule))
		@foreach($taggingRule->getParameters()->get() as $param)
			<div class="diqa-import-tagging-parameter-container">
				<input type="text"  class="diqa_taggingrule_parameters" name="diqa_taggingrule_parameters[]" value="{{$param->getParameter()}}"/>
				@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-remove-parameter', 'img' => 'remove.png', 'style' => '' ])
			</div>
		@endforeach
		@if (count($taggingRule->getParameters()->get()) == 0)
			<div class="diqa-import-tagging-parameter-container">
				<input type="text"  class="diqa_taggingrule_parameters" name="diqa_taggingrule_parameters[]" value=""/>
				@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-remove-parameter', 'img' => 'remove.png', 'style' => '' ])
			</div>
		@endif
	@else
		<div class="diqa-import-tagging-parameter-container">
			<input type="text"  class="diqa_taggingrule_parameters" name="diqa_taggingrule_parameters[]" value=""/>
			@include('specials.general.import-special-js-multibutton', ['command' => 'diqa-import-remove-parameter', 'img' => 'remove.png', 'style' => '' ])
		</div>
	@endif
	<br>
	@include('specials.general.import-special-js-button', ['command' => 'diqa-import-new-parameter', 'img' => 'add.png' ])
	@include('specials.general.import-special-js-button', ['command' => 'diqa-import-open-folder-picker' ])
	</td>
	</tr>
	
	
	<tr style="display: none;">
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
	<td colspan="2">
	<input type="submit" value="{{wfMessage('diqa-save-button')->text()}}" name="add-import-taggingrule" />
	<input type="submit" value="{{wfMessage('diqa-cancel-button')->text()}}" name="cancel-import-taggingrule" />
	</td>
	</tr>
	
	<input type="hidden" value="{{isset($taggingRule) ? $taggingRule->id : ''}}" name="diqa_import_taggingrule_id"/>
	<input type="hidden" value="{{$_SERVER['REQUEST_URI']}}" name="diqa_import_returnurl"/>
</table>


