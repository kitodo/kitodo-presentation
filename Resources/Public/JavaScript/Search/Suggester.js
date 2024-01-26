/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

$("#tx-dlf-search-query").attr({
    'autocomplete': "off",
    'role': "textbox",
    'aria-autocomplete': "list",
    'aria-haspopup': "true"
});

$(
    function () {
        // jQuery autocomplete integration
        $("#tx-dlf-search-query").autocomplete({
            source(request, response) {
                return $.post(
                    "/",
                    {
                        middleware: "dlf/search-suggest",
                        q: encodeURIComponent(request.term.toLowerCase()),
                        uHash: $("input[name='uHash']").val(),
                        solrcore: $("input[name='solrcore']").val()
                    },
                    function (data) {
                        var result = [];
                        data.forEach(function (element, index) {
                            element = element.replace(/(\?|!|:|\\)/g, "\\\$1");
                            result.push(element);
                        });
                        return response(result);
                    },
                    "json");
            },
            minLength: 3,
            appendTo: "#tx-dlf-search-suggest"
        });
    }
);
