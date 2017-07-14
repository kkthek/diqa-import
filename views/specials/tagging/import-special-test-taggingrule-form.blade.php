<?php
use DIQA\Import\Models\TaggingRule; 
?>
<div class="diqa-import-backlink">
<a href="{{wfDIQAURL('Special:DIQAtagging')}}">{{wfMessage('diqa-back-button')->text()}}</a>
</div>

<div>
<h1>{{wfMessage('diqa-import-test-article')->text()}}</h1>
<form action="{{wfDIQAURL('Special:DIQAtagging')}}" method="post">

	<!-- Artikel auswählen -->
	<input type="hidden" value="{{$taggingRule->id}}" name="diqa-import-test-rule"/>
	<div style="margin-top: 10px; margin-bottom: 10px">
	{{wfMessage('diqa-import-article')->text()}}: <input type="text" size="120" name="diqa_taggingrule_testarticle" value="{{$article}}"/>
	 <input type="hidden" name="diqa_taggingrule_testarticle_pageid" value="{{$pageid}}"/>
	</div>
	<div>
		<input type="hidden" value="{{isset($taggingRule) ? $taggingRule->id : ''}}" name="diqa_import_taggingrule_id"/>
		<input type="submit" value="{{wfMessage('diqa-test-button')->text()}}" name="test-import-taggingrule" />
		<input type="submit" value="{{wfMessage('diqa-cancel-button')->text()}}" name="cancel-import-taggingrule" />
	</div>
	
	<!-- Resultat anzeigen -->
	<h2>{{wfMessage('diqa-import-test-result')->text()}}</h2>
	@include('specials.tagging.import-special-test-taggingrule-results')
	
	<!-- Regel editieren -->
	<h2>{{wfMessage('diqa-import-edit-rule')->text()}}</h2>
	<div class="diqa-import-form"> 
	@include('specials.tagging.import-special-taggingrule-edit')
	</div>
	
</form>
</div>


