/**
 * Log page
 */
$(function() {
	
	var showNext = function(offset, jobID) {
		
		
		$('.ajax-indicator').show();
		$.ajax({
			type : "GET",
			url : WG_SERVER + WG_SCRIPTPATH + "/api.php",
			dataType : "jsonp",
			data : {
				action : "diqa_import_log",
				format : "json",
				offset : offset,
				jobID : jobID,
				command : "get-log"

			},
			success : function(data) {
				$('.ajax-indicator').hide();
				$('.next').show();
				var lines = data.diqaimportlog.lines;
				if (lines.length == 0) {
					
					alert("End of log reached");
					$('.next').hide();
					return;
				}
				var newoffset = data.diqaimportlog.offset;
				for ( var i = 0; i < lines.length; i++) {
					$('ul').append($('<li>' + lines[i] + '</li>'));
				}
				$('.next').attr('offset', newoffset);
			}, 
			error: function(data) {
				$('.ajax-indicator').hide();
				alert("Internal error occured");
			}
		});
	};
	
	$('a.next').click(function(event) { 
		var target = $(event.target);
		var offset = target.attr('offset');
		var jobID = target.attr('jobID');
		showNext(offset, jobID);
	});
	
	var searchFunction = function(search, jobID) {
		
		
		$('.ajax-indicator').show();
		$.ajax({
			type : "GET",
			url : WG_SERVER + WG_SCRIPTPATH + "/api.php",
			dataType : "jsonp",
			data : {
				action : "diqa_import_log",
				format : "json",
				search : search,
				jobID : jobID,
				command : "search-log"

			},
			success : function(data) {
				$('.ajax-indicator').hide();
				$('.next').hide();
				var lines = data.diqaimportlog.lines;
				$('ul').empty();
				if (lines.length == 0) {
					$('ul').append($('<li> - Empty result - </li>'));
					return;
				}
				
				for ( var i = 0; i < lines.length; i++) {
					$('ul').append($('<li>' + lines[i] + '</li>'));
				}
				if (lines.length == 1000) {
					alert("More than 1000 results. Please refine your search");
				}
				
			}, 
			error: function(data) {
				$('.ajax-indicator').hide();
				alert("Internal error occured");
			}
		});
	};
	
	$('input#search-button').click(function(event) { 
		var search = $('input[name=search]').val();
		var jobID = $('input#search-button').attr('jobID');
		if (search == '') {
			showNext(0, jobID);
		} else {
			searchFunction(search, jobID);
		}
	});
	$('input[name=search]').keyup(function(event) {
		var search = $('input[name=search]').val();
		var jobID = $('input#search-button').attr('jobID');
		if (event.keyCode == 13) {
			if (search == '') {
				showNext(0, jobID);
			} else {
				searchFunction(search, jobID);
			}
			
		}
	});
	
});