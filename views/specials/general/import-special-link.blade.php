<?php
global $wgScriptPath; 
?>
<div class="diqa-import-command" id="{{$command}}" style="{{isset($style) ? $style : ''}}">
@if ($id == '') 
	<a target="_blank" href="{{wfDIQAURL($page)}}?showLog=true" title="{{wfMessage($command)->text()}}">
@else
	<a target="_blank" href="{{wfDIQAURL($page)}}?showLog=true&jobID={{$id}}" title="{{wfMessage($command)->text()}}">
@endif
@if (isset($img))
<div style="height: 26px; width: 26px; background: url({{$wgScriptPath}}/extensions/Import/skins/img/{{$img}}) no-repeat"></div>
@else
[{{wfMessage($command)->text()}}]
@endif
</a>
</div>