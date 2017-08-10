<?php
global $wgScriptPath; 
?>
<form class="diqa-import-command" id="{{$command}}" action="{{wfDIQAURL($page)}}" method="post">
<input type="hidden" value="{{$id}}" name="{{$command}}"/>
<input type="hidden" value="{{isset($param) ? $param : ''}}" name="diqa-import-param"/>
<input type="hidden" value="{{$_SERVER['REQUEST_URI']}}" name="diqa_import_returnurl"/>

<a onclick="javascript: $(this).closest('form').submit();" title="{{wfMessage($command)->text()}}">
@if (isset($img))
	@if(isset($text))
		<div style="height: 16px; width: 200px; margin: 5px; background: url({{$wgScriptPath}}/extensions/Import/skins/img/{{$img}}) no-repeat">
		<span style="position: relative; top: 2px;left: 2px;" class="diqa-import-js-button-label">{{isset($text) ? $text : ''}}</span></div>
	@else
		<div style="height: 16px; width: 16px; margin: 5px; background: url({{$wgScriptPath}}/extensions/Import/skins/img/{{$img}}) no-repeat">
		</div>
	@endif
@else
[{{wfMessage($command)->text()}}]
@endif
</a>
</form>