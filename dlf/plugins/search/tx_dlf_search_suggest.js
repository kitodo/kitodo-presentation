$(
	function() {
		// jQuery autocomplete integration
		$(".tx-dlf-search-query").autocomplete({
			source: function(request, response) {
				return $.post(
					'/',
					{
						eID: "tx_dlf_search_suggest",
						q: escape(request.term),
						encrypted: $("input[name='tx_dlf[encrypted]']").val(),
						hashed: $("input[name='tx_dlf[hashed]']").val()
					},
					function(xmlData) {
						var result = new array();
						$('arr[name="suggestion"] str', xmlData).each(function(i) {
							if ($(this).text().indexOf(request.term) == 0) {
								result.push($(this).text());
							}
						});
						return response(result);
					},
					'xml');
			},
			minLength: 3,
			appendTo: ".tx-dlf-search-form"
		});
	}
);