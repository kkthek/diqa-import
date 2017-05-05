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
		$('div.diqa-import-table table').tablesorter();
		
		$('select[name=diqa_taggingrule_type]').change(function(e) {
			var option = $(e.target).find('option:selected').val();

			switch (option) {
			case 'metadata':
				$('tr#diqa-import-tagging-constraint').hide();
				$('tr#diqa-import-tagging-returnvalue').hide();
				break;
			case 'regex':
				$('tr#diqa-import-tagging-constraint').show();
				$('tr#diqa-import-tagging-returnvalue').show();
				break;
			}

		});
		
		$('div.diqa-taggingrule-container').click(function(event) { 
			$(event.target).closest('div').next().toggle();
		});

		/**
		 * Autocomplete categories for return value.
		 * 
		 */
		$("input[name=diqa_taggingrule_returnvalue]").autocomplete({
			source : function(request, response) {

				$.ajax({
					type : "GET",
					url : mw.util.wikiScript('api'),
					dataType : "jsonp",
					data : {
						action : "diqa_autocomplete",
						format : "json",
						substr : request.term,
						property : DIQA.IMPORT.fsgTitleProperty,
						schema : "Category"

					},
					success : function(data) {
						response($.map(data.pfautocomplete, function(obj, i) {
							return {
								label : obj.title,
								value : obj.title,
								data : {
									id : obj.id,
									ns : obj.ns
								}
							};
						}));
					}
				});
			},
			select : function(event, ui) {

			}

		});

		/**
		 * Check regex syntax.
		 * 
		 */
		$('input[name=diqa_taggingrule_parameters]').keyup(function(e) {
			var input = $(e.target);
			try {
				new RegExp(input.val());
				input.css({
					'background-color' : 'white'
				});
			} catch (e) {
				input.css({
					'background-color' : '#f44542'
				});
			}
		});

		/**
		 * Autocomplete on crawled properties
		 */
		$("input[name=diqa_taggingrule_crawledProperty]")
				.autocomplete(
						{
							source : function(request, response) {

								var acData;
								if (DIQA.IMPORT.extractedMetadata.length == 0) {
									acData = [ 'No crawled properties available' ];
								} else {
									acData = $
											.grep(
													DIQA.IMPORT.extractedMetadata,
													function(e, i) {
														return request.term == '*' 
															|| e.toLowerCase()
																.indexOf(request.term.toLowerCase()) > -1;
													});
								}

								response($.map(acData, function(obj, i) {
									return {
										label : obj,
										value : obj,
										data : {}
									};
								}));
							},
							select : function(event, ui) {

							}

						});

		$("input[name=diqa_taggingrule_testarticle]").autocomplete({
			source : function(request, response) {

				$.ajax({
					type : "GET",
					url : mw.util.wikiScript('api'),
					dataType : "jsonp",
					data : {
						action : "diqa_autocomplete",
						format : "json",
						substr : request.term,
						property : DIQA.IMPORT.fsgTitleProperty,
						schema : "File"

					},
					success : function(data) { 
						response($.map(data.pfautocomplete, function(obj, i) {
							return {
								label : obj.title + " ["+obj.data.fullTitle+"]",
								value : obj.title + " ["+obj.data.fullTitle+"]",
								data : {
									id : obj.id,
									ns : obj.ns,
									fullTitle: obj.data.fullTitle
								}
							};
						}));
					}
				});
			},
			select : function(event, ui) {
				 $('input[name=diqa_taggingrule_testarticle_pageid]').val(ui.item.data.fullTitle);
			}

		});

	});
})(jQuery);
