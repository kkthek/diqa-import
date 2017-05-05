<form class="diqa-import-command-nofloat" id="{{$command}}" action="{{wfDIQAURL($page)}}" method="post">
<input type="hidden" value="{{$id}}" name="{{$command}}"/>
<input type="hidden" value="{{$_SERVER['REQUEST_URI']}}" name="diqa_import_returnurl"/>
<a onclick="javascript: $(this).closest('form').submit();">
[{{wfMessage($command)->text()}}]
</a>
</form>