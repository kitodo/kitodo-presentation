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
    var currentStart = $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start']").val();
    var newStart = parseInt(currentStart) + 20;
    $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start']").val(newStart);
    $('#tx-dlf-search-in-document-form').submit();
};

/**
 * This function decreases the start parameter of the search form and submits
 * the form.
 *
 * @returns void
 */
function previousResultPage() {
    var currentStart = $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start']").val();
    var newStart = (parseInt(currentStart) > 20) ? (parseInt(currentStart) - 20) : 0;
    $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start']").val(newStart);
    $('#tx-dlf-search-in-document-form').submit();
};

/**
 * This function resets the start parameter on new queries.
 *
 * @returns void
 */
function resetStart() {
    $("#tx-dlf-search-in-document-form input[id='tx-dlf-search-in-document-start']").val(0);
}

/**
 * Add highlight effect for found search phrase.
 * @param {array} highlightIds
 * 
 * @returns void
 */
function addHighlightEffect(highlightIds) {
    if (highlightIds.length > 0) {
        highlightIds.forEach(function (highlightId) {
            console.log(highlightId)
            var targetElement = $('#' + highlightId);
    
            if (targetElement.length > 0 && !targetElement.hasClass('highlight')) {
                targetElement.addClass('highlight');
            }
        })
    }
}

/**
 * Get base URL for snippet links.
 * 
 * @returns string
 */
function getBaseUrl() {
    // Take the workview baseUrl from the form action.
    // The URL may be in the following form
    // - http://example.com/index.php?id=14
    // - http://example.com/workview (using slug on page with uid=14)
    var baseUrl = $("form#tx-dlf-search-in-document-form").attr('action');
    var url = window.location.href

    // it is specific to DDB Zeitungsportal
    // TODO: make this solution more generic
    if(baseUrl.indexOf('ddb-current/newspaper/item')) {
        baseUrl = url
    }

    if (baseUrl.indexOf('?') > 0) {
        baseUrl += '&';
    } else {
        baseUrl += '?';
    }
    console.log(baseUrl)
    return baseUrl;
}

function getNavigationButtons(start, numFound) {
    var buttons = "";

    if (start > 0) {
        buttons += '<input type="button" id="tx-dlf-search-in-document-button-previous" class="button-previous" onclick="previousResultPage();" value="' + $('#tx-dlf-search-in-document-label-previous').text() + '" />';
    }

    if (numFound > (start + 20)) {
        buttons += '<input type="button" id="tx-dlf-search-in-document-button-next" class="button-next" onclick="nextResultPage();" value="' + $('#tx-dlf-search-in-document-label-next').text() + '" />';
    }
    return buttons;
}

function search() {
    resetStart();

    $('#tx-dlf-search-in-document-loading').show();
    $('#tx-dlf-search-in-document-clearing').hide();
    $('#tx-dlf-search-in-document-button-next').hide();
    $('#tx-dlf-search-in-document-button-previous').hide();
    // Send the data using post
    $.post(
        "https://sdvtypo3ddbzeitungsportaldev.slub-dresden.de/",
        {
            eID: "tx_dlf_search_in_document",
            q: $( "input[id='tx-dlf-search-in-document-query']" ).val(),
            uid: $( "input[id='tx-dlf-search-in-document-id']" ).val(),
            start: $( "input[id='tx-dlf-search-in-document-start']" ).val(),
            encrypted: $( "input[id='tx-dlf-search-in-document-encrypted']" ).val(),
        },
        function(data) {
            var resultItems = [];
            var resultList = '<div class="results-active-indicator"></div><ul>';
            var start = -1;
            if (data['numFound'] > 0) {
                data['documents'].forEach(function (element, i) {
                    if (start < 0) {
                        start = i;
                    }
                    var searchWord = element['snippet'];
                    searchWord = searchWord.substring(searchWord.indexOf('<em>') + 4, searchWord.indexOf('</em>'));

                    var link = getBaseUrl()
                        + $("input[id='tx-dlf-search-in-document-id']").attr('name') + '=' + element['uid']
                        + '&' + $("input[id='tx-dlf-search-in-document-highlight-word']").attr('name') + '=' + encodeURIComponent(searchWord)
                        + '&' + $("input[id='tx-dlf-search-in-document-page']").attr('name') + '=' + element['page'];

                    if (element['snippet'].length > 0) {
                        resultItems[element['page']] = '<span class="structure">'
                            + $('#tx-dlf-search-in-document-label-page').text() + ' ' + element['page']
                            + '</span><br />'
                            + '<span ="textsnippet">'
                            + '<a href=\"' + link + '\">' + element['snippet'] + '</a>'
                            + '</span>';
                    }

                    // TODO: highlight found phrase in full text - verify page?
                    addHighlightEffect(element['highlight']);

                    // TODO: highlight found phrase in image
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
            resultList += getNavigationButtons(start, data['numFound'])
            $('#tx-dlf-search-in-document-results').html(resultList);
        },
        "json"
    )
        .done(function (data) {
            $('#tx-dfgviewer-sru-results-loading').hide();
            $('#tx-dfgviewer-sru-results-clearing').show();
        });
}

function clearSearch() {
    $('#tx-dlf-search-in-document-results ul').remove();
    $('.results-active-indicator').remove();
    $('#tx-dlf-search-in-document-query').val('');
}
