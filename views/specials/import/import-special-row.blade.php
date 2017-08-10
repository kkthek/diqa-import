<tr class="{{$entry->status_text == 'ERROR' ? 'diqa-import-error-row' : ''}}">
<td><input disabled="true" type="checkbox" {{$entry->active == 1 ? 'checked="checked"' : ''}} /></td>
<td><div style="word-break: break-all;">{{$entry->root_path}}</div></td>
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
@include('specials.general.import-special-command', ['command' => 'diqa-import-remove-entry', 'id' => $entry->id, 'page' => 'Special:DIQAimport', 'img' => 'remove.png' ])
@if (!$entry->isRunning() || !$entry->isActive())
@include('specials.general.import-special-command', ['command' => 'diqa-import-edit-entry', 'id' => $entry->id, 'page' => 'Special:DIQAimport', 'img' => 'edit.png' ])
@endif
@include('specials.general.import-special-command', ['command' => 'diqa-import-force-crawl', 'id' => $entry->id, 'page' => 'Special:DIQAimport', 'img' => 'force.png' ])
@if ($entry->isActive())
	@include('specials.general.import-special-command', ['command' => 'diqa-deactivate-job', 'id' => $entry->id, 'page' => 'Special:DIQAimport', 'img' => 'turn-off.png' ])
@else
	@include('specials.general.import-special-command', ['command' => 'diqa-activate-job', 'id' => $entry->id, 'page' => 'Special:DIQAimport', 'img' => 'turn-on.png' ])
@endif
@include('specials.general.import-special-link', ['command' => 'diqa-open-log', 'id' => $entry->id, 'page' => 'Special:DIQAimport', 'img' => 'log.png', 'style' => 'float: left; margin-top: 0px;' ])
</td>
</tr>