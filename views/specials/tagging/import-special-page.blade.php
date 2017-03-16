<div class="diqa-import-section">
<h1>Tagging rules</h1>

@include('specials.tagging.import-special-taggingrule-form', ['edit' => false])
@include('specials.tagging.import-special-taggingrule-table', [])
<div style="margin-top: 20px; height: 90px;">
@include('specials.general.import-special-command', ['command' => 'diqa-import-refresh', 'id' => -1, 'nofloat' => true, 'page' => 'Special:DIQAtagging' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-exporttagging', 'id' => -1, 'nofloat' => true, 'page' => 'Special:DIQAtagging' ])
<div style="margin-top: 10px;">
<form class="diqa-import-command" id="diqa-import-importtagging" action="{{wfDIQAURL('Special:DIQAtagging')}}" method="post" enctype="multipart/form-data">
<input type="file" name="diqa-import-importtagging" />
<a onclick="javascript: $(this).closest('form').submit();">
[{{wfMessage('diqa-import-importtagging')->text()}}]
</a>
</form>
</div>

</div>
</div>

