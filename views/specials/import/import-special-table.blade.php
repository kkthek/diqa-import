<div class="diqa-import-table">
<table>
<tr>
<th></th>
<th>{{wfMessage('diqa-crawler-type')->text()}}</th>
<th>{{wfMessage('diqa-import-path-fs')->text()}}</th>
<th>{{wfMessage('diqa-url-prefix')->text()}}</th>
<th>{{wfMessage('diqa-last-run-at')->text()}}</th>
<th>{{wfMessage('diqa-time-to-start')->text()}}</th>
<th>{{wfMessage('diqa-date-to-start')->text()}}</th>
<th>{{wfMessage('diqa-time-interval')->text()}}</th>
<th>{{wfMessage('diqa-documents-processed')->text()}}</th>
<th>{{wfMessage('diqa-status-text')->text()}}</th>
<th></th>
</tr>
@foreach($entries as $entry)
	@include('specials.import.import-special-row', [ 'entry' => $entry ])
@endforeach
</table>
</div>