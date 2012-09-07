$(
	function() {
		var parentForms = $('input[name^="tx_dlf[field"]').parents('form');
		if (parentForms.length == 0) {
			return;
		}
		
		var extendedSearchForm = parentForms.get(0); 
		if (extendedSearchForm == null) {
			return;
		}
		
		var queryInput = $(extendedSearchForm).find('input[name="tx_dlf[query]"]');
		if (queryInput == null) {
			return;
		}
		
		$(extendedSearchForm).submit(function() {
			var query = "";
			
			var filledSearchFields = $(extendedSearchForm).find('input[name^="tx_dlf[field"][value!=""]');
			filledSearchFields.each(function(index, element){
				var indexer = /[a-z]+_([0-9]+)/g;
				var match = indexer.exec(element.name); 
				if (match == null) {
					return false;
				}
				
				var fieldIndex = match[1];
				
				// get operator
				var operator = $('select[name="tx_dlf[operator_' + fieldIndex + ']"]').val();
				if (index > 0 || operator == "NOT") {
					if (!query) {
						query = "*";
					} 
					
					query += " " + operator;
				}
				
				// get search field selector
				var fieldSelector = $('select[name="tx_dlf[fieldSelector_' + fieldIndex + ']"]').val();
				query += " " + fieldSelector + ":" + element.value;
			});
			
			$(queryInput).val(query.replace(/^\s\s*/, '').replace(/\s\s*$/, ''));
			return true;
		});	
	}
);