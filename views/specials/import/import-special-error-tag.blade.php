@if (!$isCrawlerActive)
	<div class="diqa-import-error">
	WARNING: Crawler is probably not called periodically. Please configure cron-job as described in README.
	@include('specials.general.import-special-link', ['command' => 'diqa-open-log', 'id' => '', 'page' => 'Special:DIQAimport' ])
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
	@include('specials.general.import-special-link', ['command' => 'diqa-open-log', 'id' => '', 'page' => 'Special:DIQAimport' ])
	</div> 
@endif