$(
	function(){
	    // jQuery autocomplete integration
	    $(".autocomplete").autocomplete({ 
        	source: function( request, response ) {
        		return $.post( 
                	'/', 
                	{ 
                		eID: "tx_dlf_suggest", 
                		q:  escape(request.term),
                		encrypted: $("input[name='encrypted']").val(),
                		hashed: $("input[name='hashed']").val()
                	}, 
                	function( xmlData ) { 
						var result = new Array();
	
						$('arr[name="suggestion"] str', xmlData).each(function(i) {
							if ($(this).text().indexOf(request.term) == 0) {
								result.push($(this).text());
							}
						});
	
						return response(result);
                	},
					'xml');
            }
        });
	}
);