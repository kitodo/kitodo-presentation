$("#tx-dlf-search-query").attr({
	'autocomplete': "off",
	'role': "textbox",
	'aria-autocomplete': "list",
	'aria-haspopup': "true"
});

$(
	function() {
		// jQuery autocomplete integration
		$("#tx-dlf-search-query").autocomplete({
			source: function(request, response) {
				return $.post(
					"/",
					{
						eID: "tx_dlf_search_suggest",
						q: escape(request.term.toLowerCase()),
						encrypted: $("input[name='tx_dlf[encrypted]']").val(),
						hashed: $("input[name='tx_dlf[hashed]']").val()
					},
					function(data) {
						var result = new Array();
						$("arr[name='suggestion'] str", data).each(function(i) {
							result.push($(this).text());
						});
						return response(result);
					},
					"xml");
			},
			minLength: 3,
			appendTo: "#tx-dlf-search-suggest"
		});
	}
);