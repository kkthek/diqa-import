<?php
use DIQA\Import\Models\TaggingRule; 
?>
<div class="diqa-import-backlink">
<a href="{{wfDIQAURL('Special:DIQAtagging')}}">{{wfMessage('diqa-back-button')->text()}}</a>
</div>


<!-- Artikel auswählen -->
<div style="margin-top: 20px; margin-bottom: 20px">
<h1>{{wfMessage('diqa-import-test-article')->text()}}</h1>
<form action="{{wfDIQAURL('Special:DIQAtagging')}}" method="post">
<!-- Regel anzeigen -->
<div class="diqa-import-form"> 
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
	
	<input type="hidden" value="{{isset($taggingRule) ? $taggingRule->id : ''}}" name="diqa_import_taggingrule_id"/>
	<input type="hidden" value="{{$_SERVER['REQUEST_URI']}}" name="diqa_import_returnurl"/>
</table>
</div>
<input type="hidden" value="{{$taggingRule->id}}" name="diqa-import-test-rule"/>
<div style="margin-top: 10px; margin-bottom: 10px">
Artikel: <input type="text" size="120" name="diqa_taggingrule_testarticle" value="{{$article}}"/>
 <input type="hidden" name="diqa_taggingrule_testarticle_pageid" value="{{$pageid}}"/>
</div>

<div>
<input type="hidden" value="{{isset($taggingRule) ? $taggingRule->id : ''}}" name="diqa_import_taggingrule_id"/>
<input type="submit" value="{{wfMessage('diqa-test-button')->text()}}" name="test-import-taggingrule" />
<input type="submit" value="{{wfMessage('diqa-save-button')->text()}}" name="add-import-taggingrule" />
<input type="submit" value="{{wfMessage('diqa-cancel-button')->text()}}" name="cancel-import-taggingrule" />
</div>
</form>
</div>

<!-- Resultat anzeigen -->
@include('specials.tagging.import-special-test-taggingrule-results')
