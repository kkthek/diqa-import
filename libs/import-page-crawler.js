/**
 * Import extension (c) DIQA 2017
 * JS for Special:DIQAimport
 * 
 * @author: Kai Kühn
 * 
 */
(function($) {

	$(function() {
		
		var updateStatus = function() {
			$.ajax({
				type : "GET",
				url : mw.util.wikiScript('api'),
				dataType : "jsonp",
				data : {
					action : "diqa_import",
					format : "json",
					command : "crawler-status"

				},
				success : function(data) {
					var html = data.diqaimport.html;
					$('div#diqa-import-errors').html(html);
					setTimeout(updateStatus, 30000);
				}
			});
		};
		
		setTimeout(function() {  
			updateStatus();
		}, 30000);

		$('input[name=diqa_date_to_start]').datepicker();
		$('input[name=diqa_date_to_start]').datepicker( "option", "dateFormat", "yy-mm-dd");
		$('input[name=diqa_date_to_start]').val($('input[name=diqa_date_to_start]').attr('current'));
		
		
		

	});
})(jQuery);
