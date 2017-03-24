<?php 
use DIQA\Import\Models\CrawlerConfig;
?>
@if(!$edit)
<div class="diqa-import-add-link">
<a onclick="javascript: $('#add-crawler-config').toggle();">
[Hinzufügen]
</a>
</div>
@endif
<div id="add-crawler-config" class="diqa-import-form" style="{{$edit ? 'display: block' : 'display: none'}}">
<form action="{{wfDIQAURL('Special:DIQAimport')}}" method="post">
<table>

	<tr>
	<td>
	<span>{{wfMessage('diqa-import-active')->text()}}</span>
	</td>
	<td>
	<input name="diqa_import_active" type="checkbox" {{isset($entry) && $entry->active == 1 ? 'checked="checked"' : ''}} />
	</td>
	</tr>

	<tr>
	<td>
	<span>{{wfMessage('diqa-import-path-fs')->text()}}</span>
	</td>
	<td>
	<input type="text" size="60" name="diqa_import_import_path" value="{{isset($entry) ? $entry->getRootPath() : ''}}"/> 
	</td>
	</tr>
	
	<tr>
	<td>
	<span>{{wfMessage('diqa-url-prefix')->text()}}</span>
	</td>
	<td>
	<input type="text" size="60" name="diqa_url_path_prefix" value="{{isset($entry) ? $entry->getURLPrefix() : ''}}"/>
	</td>
	</tr>
	
	<tr>
	<td>
	<span>{{wfMessage('diqa-time-to-start')->text()}}</span>
	</td>
	<td>
	<select name="diqa_time_to_start">
		@foreach([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23] as $hour)
			<?php $hour = strlen($hour) == 1 ? "0$hour" : $hour; ?>
			<option value="{{$hour}}" {{isset($entry) && $entry->getTimeToStart() == $hour.':00:00' ? 'selected=true' : ''}}>
				{{$hour.':00:00'}}
			</option>
		@endforeach
	</select>
	
	<select name="diqa_time_interval">
		@foreach(CrawlerConfig::$INTERVALS as $label => $interval)
			<option value="{{$interval}}" {{isset($entry) && $entry->getInterval() == $interval ? 'selected=true' : ''}}>
				{{wfMessage('diqa-time-interval-'.$interval)->text()}}
			</option>
		@endforeach
	</select>
	</td>
	</tr>
	
	<tr>
	<td>
	<span>{{wfMessage('diqa-date-to-start')->text()}}</span>
	</td>
	<td>
	<input type="text" size="10" name="diqa_date_to_start" current="{{isset($entry) && $entry->getDateToStart() != '0000-00-00 00:00:00' ? $entry->getDateToStart() : ''}}"/>
	</td>
	</tr>
	
	<tr>
	<td>
	<input type="submit" value="{{wfMessage('diqa-save-button')->text()}}" name="add-import-entry" />
	@if (isset($entry))
	<input type="submit" value="{{wfMessage('diqa-cancel-button')->text()}}" name="cancel-import-entry" />
	@endif
	</td>
	</tr>
	
	<input type="hidden" value="{{isset($entry) ? $entry->id : ''}}" name="diqa_import_entry_id"/>
	<input type="hidden" value="{{$_SERVER['REQUEST_URI']}}" name="diqa_import_returnurl"/>
</table>

</form>
</div>