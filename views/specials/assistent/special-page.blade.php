<div class="diqa-import-section">
<h1>Tagging rule assistent</h1>
<form action="{{wfDIQAURL('Special:DIQAImportAssistent')}}" method="post">
<div>
<table>
<tr>
<td>
<select name="diqa_import_assistent_categories">
<option value="Prozess">Prozess</option>
<option value="Arbeitsanweisung">Arbeitsanweisung</option>
<option value="Formular">Formular</option>
</select>
</td>
<td>
<input type="hidden" name="diqa_import_assistent_path" />
<input type="submit" name="diqa_import_assistent_create" value="Create" />
</td>
</tr>
</table>
</div>

<div>
@include('specials.tagging.import-special-taggingrule-table', [])
</div>

<script>
window.DIQA = window.DIQA || {};
window.DIQA.IMPORT = window.DIQA.IMPORT || {};
window.DIQA.IMPORT.treeContent = {!!$tree!!};

</script>
<div id="diqa-importassistent-tree"></div>