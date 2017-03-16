<div class="diqa-import-table">
<table>
<tr>
<th>{{wfMessage('diqa-crawler-type')->text()}}</th>
<th>{{wfMessage('diqa-import-path-fs')->text()}}</th>
<th>{{wfMessage('diqa-url-prefix')->text()}}</th>
<th>{{wfMessage('diqa-last-run-at')->text()}}</th>
<th>{{wfMessage('diqa-update-interval')->text()}}</th>
<th>{{wfMessage('diqa-documents-processed')->text()}}</th>
<th>{{wfMessage('diqa-status-text')->text()}}</th>
<th></th>
</tr>
@foreach($entries as $entry)
	@include('specials.import.import-special-row', [ 'entry' => $entry ])
@endforeach
</table>
</div>