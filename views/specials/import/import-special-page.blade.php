<div id="diqa-import-errors">
@include('specials.import.import-special-error-tag')
</div>
@if ($needsRefresh)
<div class="diqa-import-warning">
	ACHTUNG: Wiki muss refresht werden.
	<form action="{{wfDIQAURL('Special:DIQAimport')}}" method="post">
	<input type="submit" name="diqa_import_startRefresh" value="Refresh semantic data" />
	</form>
</div>
@endif 
<div class="diqa-import-section">
<h1>Crawler configuration</h1>

@include('specials.import.import-special-form', ['edit' => false])
@include('specials.import.import-special-table', [])
<div style="margin-top: 20px">
@include('specials.general.import-special-command', ['command' => 'diqa-import-refresh', 'id' => -1,  'page' => 'Special:DIQAimport' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-rescan', 'id' => -1,  'page' => 'Special:DIQAimport' ])
</div>
</div>
