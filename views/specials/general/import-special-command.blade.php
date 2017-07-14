<?php
global $wgScriptPath; 
?>
<form class="diqa-import-command" id="{{$command}}" action="{{wfDIQAURL($page)}}" method="post">
<input type="hidden" value="{{$id}}" name="{{$command}}"/>
<input type="hidden" value="{{$_SERVER['REQUEST_URI']}}" name="diqa_import_returnurl"/>
<a onclick="javascript: $(this).closest('form').submit();" title="{{wfMessage($command)->text()}}">
@if (isset($img))
<div style="height: 16px; width: 16px; padding: 10px; background: url({{$wgScriptPath}}/extensions/Import/skins/img/{{$img}}) no-repeat"></div>
@else
[{{wfMessage($command)->text()}}]
@endif
</a>
</form>