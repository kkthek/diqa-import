/*
 * (c) DIQA 2017
 * @author: Kai Kühn
 */
(function($) {
		
	var xfs = window.XFS || {};
	
	/**
	 * Adds custom HTML to a faceted search result.
	 *  
	 * @param output
	 * @param doc
	 * @returns {String}
	 */
	xfs.addAdditionalActions = function(output, doc) {
		
		if (!doc.smwh_DIQAFileLocation_xsdvalue_t 
				|| !doc.smwh_DIQAFilename_xsdvalue_t
				|| !doc.smwh_DIQAFilesuffix_xsdvalue_t) {
			return "";
		}
		
		var crawlerConfig = DIQA.IMPORT.crawlerConfig;
		for(var i = 0; i < crawlerConfig.length; i++) {
			var baseUrl = xfs.util.addTrailingSlash(crawlerConfig[i]['root_path']);
			var prefix = xfs.util.addTrailingSlash(crawlerConfig[i]['url_prefix']);
			baseUrl = xfs.util.convertToSlashes(baseUrl);
			baseUrl = xfs.util.addTrailingSlash(baseUrl);
			prefix = xfs.util.convertToSlashes(prefix);
			prefix = xfs.util.addTrailingSlash(prefix);
			
			var filepath = doc.smwh_DIQAFileLocation_xsdvalue_t[0];
			var filename = doc.smwh_DIQAFilename_xsdvalue_t[0];
			var filesuffix = doc.smwh_DIQAFilesuffix_xsdvalue_t[0];
			
			if (filepath.indexOf(baseUrl) !== 0) {
				continue;
			}
			filepath = filepath.replace(baseUrl, prefix);
			
			dirpath = filepath.substr(0, filepath.length-filename.length-filesuffix.length-1);
			
			var html = "";
			html += '<span class="xfs_action"><a target="_blank" href="file://///'
				+ xfs.util.removeLeadingSlashes(filepath) + '">'+mw.msg( 'diqa-import-open-document' )+'</a></span>';
			html += '<span class="xfs_action"><a target="_blank" href="file://///'
				+xfs.util.removeLeadingSlashes(dirpath) + '"> | '+mw.msg('diqa-import-open-document-dir')+'</a></span>';
			return html;
		}
		
		return "";
	};
	
	
	
	xfs.util = {};
	
	xfs.util.isInCategory = function(doc, categories) {
		if (!doc.smwh_categories) return false;
		
		for(var i = 0; i < categories.length; i++) {
			if ($.inArray(categories[i], doc.smwh_categories) != -1) {
				return true;
			}
		}
		
		return false;
	};
	
	xfs.util.convertToSlashes = function(path) {
		var conv = path.replace(/\\/g, '/');
	    return conv;
	};
	
	xfs.util.removeLeadingSlashes = function(path) {
		var trimmed = path.replace(/^[\/\\]+/g, '');
	    return trimmed;
	};
	
	xfs.util.addTrailingSlash = function(path) {
		var trimmed = path.replace(/\/+$/g, '');
	    return trimmed + '/';
	};
	
})(jQuery);
