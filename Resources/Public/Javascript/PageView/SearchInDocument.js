/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

 /**
  * This function increases the start parameter of the search form and submits
  * the form.
  *
  * @returns void
  */
function nextResultPage() {
    var currentstart = $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start]']").val();
    var newstart = parseInt(currentstart) + 20;
    $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start]']").val(newstart);
    $('#tx-dlf-search-in-document-form').submit();
};

/**
 * This function decreases the start parameter of the search form and submits
 * the form.
 *
 * @returns void
 */
function previousResultPage() {
    var currentstart = $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start]']").val();
    var newstart = (parseInt(currentstart) > 20) ? (parseInt(currentstart) - 20) : 0;
    $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start]']").val(newstart);
    $('#tx-dlf-search-in-document-form').submit();
};

/**
 * This function resets the start parameter on new queries.
 *
 * @returns void
 */
function resetStart() {
    $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start]']").val(0);
}

$(document).ready(function() {
    $("#tx-dlf-search-in-document-form").submit(function(event) {
        // Stop form from submitting normally
        event.preventDefault();
        $('#tx-dlf-search-in-document-loading').show();
        $('#tx-dlf-search-in-document-clearing').hide();
        $('#tx-dlf-search-in-document-button-next').hide();
        $('#tx-dlf-search-in-document-button-previous').hide();
        // Send the data using post
        $.post(
            "/",
            {
                eID: "tx_dlf_search_in_document",
                q: $( "input[id='tx-dlf-search-in-document-query]']" ).val(),
                uid: $( "input[id='tx-dlf-search-in-document-id]']" ).val(),
                start: $( "input[id='tx-dlf-search-in-document-start]']" ).val(),
                encrypted: $( "input[id='tx-dlf-search-in-document-encrypted]']" ).val(),
            },
            function(data) {
                var resultItems = [];
                var resultList = '<div class="results-active-indicator"></div><ul>';
                var start = -1;
                if (data['numFound'] > 0) {
                    // Take the workview baseUrl from the form action.
                    // The URL may be in the following form
                    // - http://example.com/index.php?id=14
                    // - http://example.com/workview (using slug on page with uid=14)
                    var baseUrl = $("form#tx-dlf-search-in-document-form").attr('action');

                    if (baseUrl.indexOf('?') > 0) {
                        baseUrl += '&';
                    } else {
                        baseUrl += '?';
                    }
                    data['documents'].forEach(function (element, i) {
                        if (start < 0) {
                            start = i;
                        }
                        var searchWord = element['snippet'];
                        searchWord = searchWord.substring(searchWord.indexOf('<em>') + 4, searchWord.indexOf('</em>'));

                        var link = baseUrl
                            + 'tx_dlf[id]=' + element['uid']
                            + '&tx_dlf[highlight_word]=' + encodeURIComponent(searchWord)
                            + '&tx_dlf[page]=' + element['page'];

                        if (element['snippet'].length > 0) {
                            resultItems[element['page']] = '<span class="structure">'
                                + $('#tx-dlf-search-in-document-label-page').text() + ' ' + element['page']
                                + '</span><br />'
                                + '<span ="textsnippet">'
                                + '<a href=\"' + link + '\">' + element['snippet'] + '</a>'
                                + '</span>';
                        }
                    });
                    // Sort result by page.
                    resultItems.sort(function (a, b) {
                        return a - b;
                    });
                    resultItems.forEach(function (item, index) {
                        resultList += '<li>' + item + '</li>';
                    });
                } else {
                    resultList += '<li class="noresult">' + $('#tx-dlf-search-in-document-label-noresult').text() + '</li>';
                }
                resultList += '</ul>';
                if (start > 0) {
                    resultList += '<input type="button" id="tx-dlf-search-in-document-button-previous" class="button-previous" onclick="previousResultPage();" value="' + $('#tx-dlf-search-in-document-label-previous').text() + '" />';
                }
                if (data['numFound'] > (start + 20)) {
                    resultList += '<input type="button" id="tx-dlf-search-in-document-button-next" class="button-next" onclick="nextResultPage();" value="' + $('#tx-dlf-search-in-document-label-next').text() + '" />';
                }
                $('#tx-dlf-search-in-document-results').html(resultList);
            },
            "json"
        )
        .done(function( data ) {
            $('#tx-dfgviewer-sru-results-loading').hide();
            $('#tx-dfgviewer-sru-results-clearing').show();
        });
    });
      // clearing button
    $('#tx-dlf-search-in-document-clearing').click(function() {
        $('#tx-dlf-search-in-document-results ul').remove();
        $('.results-active-indicator').remove();
        $('#tx-dlf-search-in-document-query').val('');
    });
});
