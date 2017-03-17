@if(!$edit)
<div class="diqa-import-add-link">
<a onclick="javascript: $('#add-crawler-config').toggle();">
[Hinzufügen]
</a>
</div>
@endif
<div id="add-crawler-config" class="diqa-import-form" style="{{$edit ? 'display: block' : 'display: none'}}">
<form action="{{wfDIQAURL('Special:DIQAimport')}}" method="post">
<table>

	<tr>
	<td>
	<span>{{wfMessage('diqa-import-active')->text()}}</span>
	</td>
	<td>
	<input name="diqa_import_active" type="checkbox" {{isset($entry) && $entry->active == 1 ? 'checked="checked"' : ''}} />
	</td>
	</tr>

	<tr>
	<td>
	<span>{{wfMessage('diqa-import-path-fs')->text()}}</span>
	</td>
	<td>
	<input type="text" size="60" name="diqa_import_import_path" value="{{isset($entry) ? $entry->getRootPath() : ''}}"/> 
	</td>
	</tr>
	
	<tr>
	<td>
	<span>{{wfMessage('diqa-url-prefix')->text()}}</span>
	</td>
	<td>
	<input type="text" size="60" name="diqa_url_path_prefix" value="{{isset($entry) ? $entry->getURLPrefix() : ''}}"/>
	</td>
	</tr>
	
	<tr>
	<td>
	<span>{{wfMessage('diqa-update-interval')->text()}}</span>
	</td>
	<td>
	<input type="text" size="10" name="diqa_update_interval" value="{{isset($entry) ? $entry->getRunInterval() : ''}}"/>
	</td>
	</tr>
	
	<tr>
	<td>
	<input type="submit" value="{{wfMessage('diqa-save-button')->text()}}" name="add-import-entry" />
	@if (isset($entry))
	<input type="submit" value="{{wfMessage('diqa-cancel-button')->text()}}" name="cancel-import-entry" />
	@endif
	</td>
	</tr>
	
	<input type="hidden" value="{{isset($entry) ? $entry->id : ''}}" name="diqa_import_entry_id"/>
	<input type="hidden" value="{{$_SERVER['REQUEST_URI']}}" name="diqa_import_returnurl"/>
</table>

</form>
</div>