<div class="diqa-import-command" id="{{$command}}">
@if ($id == '') 
	<a target="_blank" href="{{wfDIQAURL($page)}}?showLog=true">
@else
	<a target="_blank" href="{{wfDIQAURL($page)}}?showLog=true&jobID={{$id}}">
@endif
[{{wfMessage($command)->text()}}]
</a>
</div>