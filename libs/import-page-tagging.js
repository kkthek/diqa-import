/**
 * Import extension (c) DIQA 2017
 * JS for Special:DIQAtagging
 * 
 * @author: Kai Kühn
 * 
 */
(function($) {

	var Ajax = function() {
		var that = {};

		/**
		 * Returns the dialog HTML
		 */
		that.getFolderPickerDialog = function(callback, callbackError) {

			var data = {
				action : 'diqa_import',
				command : 'get-folder-picker',
				format : 'json'
			};

			$.ajax({
				type : "GET",
				url : mw.util.wikiScript('api'),
				data : data,
				dataType : 'json',
				success : function(jsondata) {
					callback(jsondata);

				},
				error : function(jsondata) {
					callbackError(jsondata);
				}
			});

		};

		return that;
	};
	
	var FolderPickerDialog = function() {
		
		var that = {};
		
		that.dialog = null;
		
		var showDialog = function() {
			var dialog = $('#folder-picker-dialog').modal({
				"backdrop" : "static",
				"keyboard" : true,
				"show" : true

			}).on('shown.bs.modal', function(e) {
				
			});
			return dialog;
		};
		
		that.openDialog = function() {
			
			if (that.dialog) {
				showDialog();
				return;
			}
			
			var ajaxIndicator = new DIQAUTIL.Util.AjaxIndicator();
			ajaxIndicator.setGlobalLoading(true);
			
			new Ajax().getFolderPickerDialog(function(jsondata) { 
				
				ajaxIndicator.setGlobalLoading(false);
				var html = jsondata.diqaimport.html;
				$('div#folder-picker-dialog').remove();
				$('body').append($(html));
				that.dialog = showDialog();
				
				that.initializeDialog(that.dialog);

			}, function() { 
				// callback on ajax-error
				ajaxIndicator.setGlobalLoading(false);
			});
			
			
		};
		
		that.onOK = function() {
			if (!that.selectedNode) {
				alert("Folder auswählen");
				return;
			}
			var path = that.selectedNode.getKeyPath();
			path = mw.RegExp.escape(path);
		    $('input[name=diqa_taggingrule_parameters]').val(path);
		    that.dialog.modal('hide');
		};
		
		that.initializeDialog = function(dialog) {
			$("#tree", dialog).fancytree({ 
				 source: window.DIQA.IMPORT.treeContent,
				 toggleEffect: false, // REQUIRED! incompatibility with jQuery.ui used with Mediawiki
				 click: function(event, data) {
					    var node = data.node,
					        // Only for click and dblclick events:
					        // 'title' | 'prefix' | 'expander' | 'checkbox' | 'icon'
					        targetType = data.targetType;

					    
					    that.selectedNode = node;
					    
					  },
			}); 
			
			 var tree = $("#tree", dialog).fancytree("getTree");
			 
			 tree.visit(function(node){
				    node.setExpanded(true);
			 });
			 
			 $('button[action=ok]').click(that.onOK);
		};
		
		return that;
	};
	
	$(function() {

		/**
		 * Unfold tagging rules from properties
		 */
		$('div.diqa-taggingrule-header').click(
				function(event) {

					$(event.target).closest('div.diqa-taggingrule-container')
							.find('div.diqa-taggingrule-table').toggle();
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
		 * Folder picker dialog
		 */
		var folderPickerDialog;
		$('a#diqa-import-open-folder-picker').click(function(event) { 
			
			if (!folderPickerDialog) {
				folderPickerDialog = new FolderPickerDialog();  
			}
			folderPickerDialog.openDialog();
			
		});
	});
})(jQuery);
