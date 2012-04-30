$(
	function(){
		$('.typeahead').typeahead({
	    	source: function (typeahead, query) {
	        	return $.post(
		        	'/', { eID: "tx_dlf_suggest", q: query },
		        	function (data) {
			            var strings = new Array();
					
						$('arr[name="suggestion"] str', data).each(function(i) {
							strings.push($(this).text());
						});

			            return typeahead.process(strings);
					},
					'xml');
	    	}
	    });
	}
);