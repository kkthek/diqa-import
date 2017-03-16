(function($) {


	
	$(function() {
		$("#diqa-importassistent-tree").fancytree({ 
			 source: window.DIQA.IMPORT.treeContent,
			 toggleEffect: false, // REQUIRED! incompatibility with jQuery.ui used with Mediawiki
			 click: function(event, data) {
				    var node = data.node,
				        // Only for click and dblclick events:
				        // 'title' | 'prefix' | 'expander' | 'checkbox' | 'icon'
				        targetType = data.targetType;

				    var path = node.getKeyPath();
				    $('input[name=diqa_import_assistent_path]').val(path);
				  },
		}); 
		
		 var tree = $("#diqa-importassistent-tree").fancytree("getTree");
		 
		 tree.visit(function(node){
			    node.setExpanded(true);
		 });
	});
	
})(jQuery);

