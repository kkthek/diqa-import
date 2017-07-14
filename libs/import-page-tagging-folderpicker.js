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
	
	var FolderPickerDialog = function(metadata, paths) {
		
		var that = {};
		
		that.dialog = null;
		that.metadata = metadata;
		that.paths = paths;
		
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
			if (that.selectedNodes.length == 0) {
				alert("Folder auswählen");
				return;
			}
		    var parameters = $('div.diqa-import-tagging-parameter-container');
		    var lastParameter = parameters.eq(parameters.length-1);
		    
		    $(that.selectedNodes).each(function(i, e) { 
		    	var path = e.getKeyPath();
		    	path = mw.RegExp.escape(path);
		    	var cloneParameter = lastParameter.clone(true);
				cloneParameter.find('input').val(path);
				cloneParameter.insertAfter(lastParameter);
		    	
		    });
		    parameters.remove();
		    that.dialog.modal('hide');
		};
		
		that.initializeDialog = function(dialog) {
			that.selectedNodes = [];
			$("#tree", dialog).fancytree({ 
				 checkbox: true,
				 source: window.DIQA.IMPORT.treeContent,
				 toggleEffect: false, // REQUIRED! incompatibility with jQuery.ui used with Mediawiki
				 click: function(event, data) {
					    var node = data.node,
					        // Only for click and dblclick events:
					        // 'title' | 'prefix' | 'expander' | 'checkbox' | 'icon'
					        targetType = data.targetType;

					    if (!node.isSelected()) {
					    	that.selectedNodes.push(node);
					    } else {
					    	that.selectedNodes = $.grep(that.selectedNodes, function(n) { return n.key != node.key; });
					    }
					    	
					    
				 },
				 renderNode: function(event, data) {
				        // Optionally tweak data.node.span
				        var node = data.node;
				        
				        var foundSynonym = false;
				        if (that.metadata) {
					        $.each(that.metadata['synonyms'], function(i, e) { 
					        	 if (node.title.toLowerCase().indexOf(e.toLowerCase()) > -1) {
					        		 foundSynonym = true;
					        	 }
					        });
					        if (node.title.toLowerCase().indexOf(that.metadata['title'].toLowerCase()) > -1) {
				        		 foundSynonym = true;
				        	 }
				       }
				       var $span = $(node.span);
				       if (foundSynonym) {
				          $span.find("> span.fancytree-title").text(">> " + node.title).css({
				            fontStyle: "italic"
				          });
				       } else {
				    	   $span.find("> span.fancytree-title").text(node.title);
				       }
				        
				 }

			}); 
			
			 var tree = $("#tree", dialog).fancytree("getTree");
			 
			 tree.visit(function(node){
				    node.setExpanded(true);
				    var path = node.getKeyPath();
				    var result = $.grep(that.paths, function(e) { 
				    	return e == path;
				    });
				    if (result.length > 0) {
				    	node.setSelected();
				    }
			 });
			 
			 $('button[action=ok]').click(that.onOK);
			 $('#diqa-import-fold').click(that.fold);
			 $('#diqa-import-unfold').click(that.unfold);
		};
		
		that.fold = function() {
			var tree = $("#tree", that.dialog).fancytree("getTree");
			 tree.visit(function(node){
				    node.setExpanded(false);
			 });
		};
		
		that.unfold = function() {
			var tree = $("#tree", that.dialog).fancytree("getTree");
			 tree.visit(function(node){
				    node.setExpanded(true);
			 });
		};
		
		return that;
	};
	
	$(function() {

		/**
		 * Folder picker dialog
		 */
		mw.RegExp.unescape = function(str) {
			return str.replace( /\\([\\{}()|.?*+\-\^$\[\]])/g, '$1' );
		};
		var folderPickerDialog;
		$('a#diqa-import-open-folder-picker').click(function(event) { 
			
			var returnValuePicker = window.DIQA.IMPORT.returnValuePicker;
			var metadata = returnValuePicker.getMetadataForSelectedValue();
			var paths = $.map($('input.diqa_taggingrule_parameters'), function(e) {
				return mw.RegExp.unescape($(e).val());
			});
			
			folderPickerDialog = new FolderPickerDialog(metadata, paths);  
			
			folderPickerDialog.openDialog();
			
		});		
		
	});
})(jQuery);
