<tr>
<td><input disabled="true" type="checkbox" {{$entry->active == 1 ? 'checked="checked"' : ''}} /></td>
<td>{{$entry->crawler_type}}</td>
<td>{{$entry->root_path}}</td>
<td>{{$entry->url_prefix}}</td>
<td>{{$entry->last_run_at}}</td>
<td>{{$entry->run_interval}}</td>
<td>{{$entry->documents_processed}}</td>
<td>{{$entry->status_text}}</td>
<td>
@include('specials.general.import-special-command', ['command' => 'diqa-import-remove-entry', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-edit-entry', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-force-crawl', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
</td>
</tr>