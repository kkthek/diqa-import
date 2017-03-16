<form class="diqa-import-command{{isset($nofloat) && $nofloat === true ? '-nofloat' : ''}}" id="{{$command}}" action="{{wfDIQAURL($page)}}" method="post">
<input type="hidden" value="{{$id}}" name="{{$command}}"/>
<a onclick="javascript: $(this).closest('form').submit();">
[{{wfMessage($command)->text()}}]
</a>
</form>