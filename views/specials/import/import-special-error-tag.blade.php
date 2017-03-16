@if (!$isCrawlerActive)
	<div class="diqa-import-error">
	WARNING: Crawler is probably not called periodically. Please configure cron-job as described in README.
	</div> 
@endif
@if (count($crawlerErrors) > 0)
	<div class="diqa-import-error">
	<h2>Errors</h2>
	<ul>
	@foreach($crawlerErrors as $error)
		<li>{{$error}}</li>
	@endforeach
	</ul>
	</div> 
@endif