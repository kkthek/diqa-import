<?php
global $wgScriptPath; 
?>
<a class="diqa-import-js-button {{$command}}" title="{{wfMessage($command)->text()}}" style="{{isset($style) ? $style : ''}}">
@if (isset($img))
<div style="height: 26px; width: 26px; background: url({{$wgScriptPath}}/extensions/Import/skins/img/{{$img}}) no-repeat"></div>
@else
[{{wfMessage($command)->text()}}]
@endif
</a>