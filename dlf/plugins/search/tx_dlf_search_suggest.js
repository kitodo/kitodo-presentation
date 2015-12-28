/***************************************************************
*  Copyright notice
*
*  (c) 2011 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

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
						q: encodeURIComponent(request.term.toLowerCase()),
						encrypted: $("input[name='tx_dlf[encrypted]']").val(),
						hashed: $("input[name='tx_dlf[hashed]']").val()
					},
					function(data) {
						var result = [];
						var option = "";
						$("arr[name='suggestion'] str", data).each(function(i) {
							option = $(this).text();
							option = option.replace(/(\?|!|:)/g, "\\\$1");
							result.push(option);
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
