<tr class="{{$entry->status_text == 'ERROR' ? 'diqa-import-error-row' : ''}}">
<td><input disabled="true" type="checkbox" {{$entry->active == 1 ? 'checked="checked"' : ''}} /></td>
<td>{{$entry->root_path}}</td>
<td>{{$entry->url_prefix != '' ? $entry->url_prefix : wfMessage('diqa-import-please-enter')->text()}}</td>
<td>{{is_null($entry->last_run_at) ? '-' : $entry->last_run_at}}</td>
<td>{{$entry->getNextRun()}}</td>
<td>{{date("H:i:s", strtotime($entry->date_to_start))}}</td>
<td>{{date("Y-m-d", strtotime($entry->date_to_start))}}</td>
<td>{{wfMessage('diqa-time-interval-'.$entry->time_interval)->text()}}</td>
<td>{{$entry->documents_processed}}</td>
<td>{{$entry->status_text == '' ? '-' : $entry->status_text}}</td>
<td>{{$entry->isRunning() ? 'RUNNING' : 'STOPPED'}}</td>
<td style="width: 150px;padding-bottom: 10px;padding-top: 10px">
@include('specials.general.import-special-command', ['command' => 'diqa-import-remove-entry', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
@if (!$entry->isRunning() || !$entry->isActive())
@include('specials.general.import-special-command', ['command' => 'diqa-import-edit-entry', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
@endif
@include('specials.general.import-special-command', ['command' => 'diqa-import-force-crawl', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
@if ($entry->isActive())
	@include('specials.general.import-special-command', ['command' => 'diqa-deactivate-job', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
@else
	@include('specials.general.import-special-command', ['command' => 'diqa-activate-job', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
@endif
@include('specials.general.import-special-link', ['command' => 'diqa-open-log', 'id' => $entry->id, 'page' => 'Special:DIQAimport' ])
</td>
</tr>