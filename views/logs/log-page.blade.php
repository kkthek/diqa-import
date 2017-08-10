<?php
global $wgServer, $wgScriptPath; 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Log</title>
  
	
	<script>
	var WG_SERVER = "{{$wgServer}}";
	var WG_SCRIPTPATH = "{{$wgScriptPath}}";
	</script>
	<script src="{{$wgServer}}{{$wgScriptPath}}/extensions/Import/libs/jquery-3.2.1.min.js"></script>
	<script src="{{$wgServer}}{{$wgScriptPath}}/extensions/Import/libs/log.js"></script>
	<style>
	a.next {
		cursor: pointer;
	}
	</style>
</head>

<body>
@if (count($rows) === 0)
	<p>Log is empty</p>
@else
<p>Search: <input name="search" type="text" size="80" /><input type="button" id="search-button" value="Search" jobID="{{$jobID}}"/>
<img class="ajax-indicator" src="{{$wgServer}}{{$wgScriptPath}}/extensions/Import/skins/ajax-loader.gif" style="display: none;"/></p>
<ul>
<?php $i = 0;?>
@foreach($rows as $row)
	<li>{{$row}}</li>
	<?php $i++;?>
@endforeach
</ul>
<a class="next" offset="{{$offset}}" jobID="{{$jobID}}">Next 1000 lines</a>
<img class="ajax-indicator" src="{{$wgServer}}{{$wgScriptPath}}/extensions/Import/skins/ajax-loader.gif" style="display: none;"/>
@endif
</body>
</html>
