<div id="diqa-import-errors">
@include('specials.import.import-special-error-tag')
</div>
 
<div class="diqa-import-section">
<h1>Crawler configuration</h1>

@include('specials.import.import-special-form', ['edit' => false])
@include('specials.import.import-special-table', [])
<div style="margin-top: 20px">
@include('specials.general.import-special-command', ['command' => 'diqa-import-rescan', 'id' => -1,  'page' => 'Special:DIQAimport' ])
</div>
</div>
