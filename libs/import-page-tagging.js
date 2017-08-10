/**
 * Import extension (c) DIQA 2017
 * JS for Special:DIQAtagging
 * 
 * @author: Kai Kühn
 * 
 */
(function($) {

	/**
	 * Picker for rule type
	 */
	var RuleTypePicker = function() {
		var that = {};
		
		that.initialize = function() {
			var value = $('select[name=diqa_taggingrule_type]').find('option:selected').val();
			that.selectValue(value);
			
			$('select[name=diqa_taggingrule_type]').change(function(e) {
				var value = $(e.target).find('option:selected').val();
				that.selectValue(value);
				

			});
		};
		
		that.selectValue = function(value) {
			switch (value) {
			case 'metadata':
				$('tr#diqa-import-tagging-constraint').hide();
				$('tr#diqa-import-tagging-returnvalue').hide();
				$('input[name=diqa_taggingrule_crawledProperty]').removeProp('readonly');
				$('a#diqa-import-open-folder-picker').hide();
				$('tr#diqa-import-tagging-crawledProperty').show();
				break;
			case 'regex':
				$('tr#diqa-import-tagging-constraint').show();
				$('tr#diqa-import-tagging-returnvalue').show();
				$('span#diqa-import-return-value-hint').show();
				$('input[name=diqa_taggingrule_crawledProperty]').removeProp('readonly');
				$('a#diqa-import-open-folder-picker').hide();
				$('tr#diqa-import-tagging-crawledProperty').show();
				$('a.diqa-import-remove-parameter').show();
				$('a#diqa-import-new-parameter').show();
				$('input.diqa_taggingrule_parameters').removeProp('readonly');
				$('span#diqa-import-tagging-constraint-label').html(mw.msg('diqa-import-regex-label'));
				break;
			
			case 'regex-path':
				$('tr#diqa-import-tagging-constraint').show();
				$('span#diqa-import-return-value-hint').hide();
				$('tr#diqa-import-tagging-returnvalue').show();
				$('input[name=diqa_taggingrule_crawledProperty]').val('DIQAFileLocation');
				$('input[name=diqa_taggingrule_crawledProperty]').prop('readonly', 'readonly');
				$('a#diqa-import-open-folder-picker').show();
				$('tr#diqa-import-tagging-crawledProperty').hide();
				$('a.diqa-import-remove-parameter').hide();
				$('a#diqa-import-new-parameter').hide();
				$('input.diqa_taggingrule_parameters').prop('readonly', 'readonly');
				$('span#diqa-import-tagging-constraint-label').html(mw.msg('diqa-import-regex-path-label'));
				break;
			}
		};
		return that;
	};
	
	
	
	/**
	 * Picker for return value (combobox)
	 */
	var ReturnValuePicker = function() {
		var that = {};
		
		that.initialize = function() {
			
			var returnValueField = $("select[name=diqa_taggingrule_returnvalue]");
			var attributeField = $('select[name=diqa_taggingrule_attribute]');
			
			returnValueField.combobox();
			var valueToSelect = returnValueField.val();
			var initialAttribute = attributeField.find('option:selected').val();
			that.selectValue(initialAttribute, valueToSelect);
			attributeField.change(function(event) { 
				var attribute = $(event.target).val();
				that.selectValue(attribute);
			});
		};
		
		that.selectValue = function(attribute, valueToSelect) {
			var select = $("select[name=diqa_taggingrule_returnvalue]");
			select.empty();
			if (DIQA.IMPORT.AttributeReturnValueMapping[attribute]) {
				var queryData = DIQA.IMPORT.AttributeReturnValueMapping[attribute];
				$.each(queryData, function(i, e) {
					var option = $('<option>').attr('value', e.mwTitle).html(e.title);
					if (valueToSelect == e.mwTitle) {
						option.attr('selected','selected');
					}
					select.append(option);
				});
			}
		};
		
		that.getMetadataForSelectedValue = function() {
			var value = $('select[name=diqa_taggingrule_attribute] option:selected').val();
			if (DIQA.IMPORT.AttributeReturnValueMapping[value]) {
				var instance = $("select[name=diqa_taggingrule_returnvalue]").val();
				return DIQA.IMPORT.AttributeReturnValueMapping[value][instance];
			}
			return null;
		};
		return that;
	};
	
	/**
	 * Rule sorting functionality
	 */
	var RuleSorter = function() {
		var that = {};
		
		/**
		 * Switch function. Replace two elements in DOM
		 */
		$.fn.swapWith = function(to) {
		    return this.each(function() {
		        var copy_to = $(to).clone(true);
		        var copy_from = $(this).clone(true);
		        $(to).replaceWith(copy_from);
		        $(this).replaceWith(copy_to);
		    });
		};
		
		that.initialize = function() {
			
			
			$('a.diqa-import-sortdown-rule').click(function(event) { 
				var container = $(event.target).closest('div.diqa-taggingrule-container');
				var tr = $(event.target).closest('tr');
				var next_tr = tr.next();
				if (next_tr.attr('ruleID')) {
					tr.swapWith(next_tr);
					that.showSaveButton(container);
				}
			});
			
			$('a.diqa-import-sortup-rule').click(function(event) { 
				var container = $(event.target).closest('div.diqa-taggingrule-container');
				var tr = $(event.target).closest('tr');
				var prev_tr = tr.prev();
				if (prev_tr.attr('ruleID')) {
					tr.swapWith(prev_tr);
					that.showSaveButton(container);
				}
			});
			
			$('.diqa-import-save-rule-order').click(function(event) { 
				event.stopPropagation();
				event.preventDefault();
				var container = $(event.target).closest('div.diqa-taggingrule-container');
				var rows = container.find('tr');
				var ruleIDs = [];
				rows.each(function(i,e) {
					var ruleid = $(e).attr('ruleID');
					if (ruleid) { 
						ruleIDs.push(ruleid);
					}
				});
				that.saveRuleOrder(ruleIDs, function() {
					if (timeoutHandle) clearTimeout(timeoutHandle);
					$('a.diqa-import-save-rule-order', container).hide();
				});
				
			});
			
			/**
			 * Save indicator blinks to inform user
			 * 
			 * @param container Attribute container
			 */
			var timeoutHandle = null;
			that.showSaveButton = function(container) {
				var toggle = true;
				var f = function() { 
					if (timeoutHandle) clearTimeout(timeoutHandle);
					timeoutHandle = setTimeout(f, 1000);
					if (toggle) { 
						$('a.diqa-import-save-rule-order', container).fadeIn( "slow");
					} else {
						$('a.diqa-import-save-rule-order', container).fadeOut( "slow");
					}
					toggle = !toggle;
					
				};
				f();
			};
			
			
			/**
			 * Saves rule order.
			 * 
			 * @param ruleIDs Array of rule-IDs saved in the given order
			 * @param callbackOnSuccess Is called when the rule order could be saved successfully
			 */
			that.saveRuleOrder = function(ruleIDs, callbackOnSuccess) {
				var ajaxIndicator = new DIQAUTIL.Util.AjaxIndicator();
				ajaxIndicator.setGlobalLoading(true);
				$.ajax({
					type : "GET",
					url : mw.util.wikiScript('api'),
					dataType : "jsonp",
					data : {
						action : "diqa_import",
						format : "json",
						command : 'reorder-rules',
						ruleIDs : ruleIDs.join(',')

					},
					success : function(data) {
						ajaxIndicator.setGlobalLoading(false);
						callbackOnSuccess();
					},
					error : function(data) {
						ajaxIndicator.setGlobalLoading(false);
						alert("Internal error occured");
					}
				});
			};
		};
		return that;
	};
	
	
	
	
	$(function() {

		window.DIQA = window.DIQA || {};
		window.DIQA.IMPORT = window.DIQA.IMPORT || {};
		
		window.DIQA.IMPORT.ruleTypePicker = new RuleTypePicker();
		window.DIQA.IMPORT.ruleTypePicker.initialize();
		
		window.DIQA.IMPORT.returnValuePicker = new ReturnValuePicker();
		window.DIQA.IMPORT.returnValuePicker.initialize();
		
		window.DIQA.IMPORT.ruleSorter = new RuleSorter();
		window.DIQA.IMPORT.ruleSorter.initialize();
		
		/**
		 * Unfold tagging rules from properties
		 */
		$('div.diqa-taggingrule-header').click(
				function(event) {

					$(event.target).closest('div.diqa-taggingrule-container')
							.find('div.diqa-taggingrule-table').toggle();
					
					$('.diqa-import-unfold-link', event.target).toggle();
					$('.diqa-import-fold-link', event.target).toggle();
				});

		/**
		 * Check if XML rule file is selected.
		 */
		$('form#diqa-import-importtagging')
				.submit(
						function() {
							if ($('input[name=diqa-import-importtagging]').get(
									0).files.length === 0) {
								alert(mw.msg('diqa-import-no-file-selected'));
								return false;
							}
							return true;
						});

		
		/**
		 * Check regex syntax.
		 * 
		 */
		$('input[class=diqa_taggingrule_parameters]').keyup(function(e) {
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
																|| e
																		.toLowerCase()
																		.indexOf(
																				request.term
																						.toLowerCase()) > -1;
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

		/**
		 * Autocomplete on test article
		 */
		$("input[name=diqa_taggingrule_testarticle]").autocomplete(
				{
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
								response($.map(data.pfautocomplete, function(
										obj, i) {
									return {
										label : obj.title + " ["
												+ obj.data.fullTitle + "]",
										value : obj.title + " ["
												+ obj.data.fullTitle + "]",
										data : {
											id : obj.id,
											ns : obj.ns,
											fullTitle : obj.data.fullTitle
										}
									};
								}));
							}
						});
					},
					select : function(event, ui) {
						$('input[name=diqa_taggingrule_testarticle_pageid]')
								.val(ui.item.data.fullTitle);
					}

				});

		
		/**
		 * New parameter button
		 */
		$('#diqa-import-new-parameter').click(function(event) {
			var parameters = $('div.diqa-import-tagging-parameter-container');
			var lastParameter = parameters.eq(parameters.length-1);
			var cloneParameter = lastParameter.clone(true);
			cloneParameter.find('input').val('');
			cloneParameter.insertAfter(lastParameter);
			
		});
		
		/**
		 * Remove parameter button
		 */
		$('.diqa-import-remove-parameter').click(function(event) {
			var parameters = $('div.diqa-import-tagging-parameter-container');
			if (parameters.length == 1) {
				return;
			}
			var input = $(event.target).closest('div.diqa-import-tagging-parameter-container');
			input.remove();
			
		});
		
		/**
		 * Make sure that the attribute form field is enabled before the form is sent.
		 * Otherwise it is omitted from the HTTP request.
		 */
		$('form').on('submit', function() {
		    $('select[name=diqa_taggingrule_attribute]').prop('disabled', false);
		});
		
		
		
	});
})(jQuery);
