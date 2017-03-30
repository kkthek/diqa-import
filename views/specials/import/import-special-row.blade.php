<tr>
<td><input disabled="true" type="checkbox" {{$entry->active == 1 ? 'checked="checked"' : ''}} /></td>
<td>{{$entry->crawler_type}}</td>
<td>{{$entry->root_path}}</td>
<td>{{$entry->url_prefix != '' ? $entry->url_prefix : wfMessage('diqa-import-please-enter')->text()}}</td>
<td>{{is_null($entry->last_run_at) ? '-' : $entry->last_run_at}}</td>
<td>{{date("H:i:s", strtotime($entry->date_to_start))}}</td>
<td>{{date("Y-m-d", strtotime($entry->date_to_start))}}</td>
<td>{{wfMessage('diqa-time-interval-'.$entry->time_interval)->text()}}</td>
<td>{{$entry->documents_processed}}</td>
<td>{{$entry->status_text == '' ? '-' : $entry->status_text}}</td>
<td>
@include('specials.general.import-special-command', ['command' => 'diqa-import-remove-entry', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-edit-entry', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
@include('specials.general.import-special-command', ['command' => 'diqa-import-force-crawl', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
</td>
</tr>