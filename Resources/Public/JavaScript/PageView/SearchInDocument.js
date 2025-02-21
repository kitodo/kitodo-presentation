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
}

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
}

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
            var targetElement = $('#' + highlightId);

            if (targetElement.length > 0 && !targetElement.hasClass('highlight')) {
                targetElement.addClass('highlight');
            }
        });
    }
}

/**
 * Get base URL for snippet links.
 *
 * @param {string} id
 *
 * @returns {string}
 */
function getBaseUrl(id) {
    // Take the workview baseUrl from the form action.
    // The URL may be in the following form
    // - http://example.com/index.php?id=14
    // - http://example.com/workview (using slug on page with uid=14)
    var baseUrl = $("form#tx-dlf-search-in-document-form").attr('action');

    // check if action URL contains id, if not, get URL from window
    if(baseUrl === undefined || baseUrl.split('?')[0].indexOf(id) === -1) {
        baseUrl = $(location).attr('href');
    }

    return baseUrl;
}

/**
 * Get highlight coordinates as string separated by ';'.
 *
 * @param {string} highlight
 *
 * @returns {string}
 */
function getHighlights(highlight) {
    var highlights = "";

    for(var i = 0; i < highlight.length; i++) {
        if (highlights === "") {
            highlights += highlight[i];
        } else {
            if(highlights.indexOf(highlight[i]) === -1) {
                highlights += ';' + highlight[i];
            }
        }
    }

    return highlights;
}

/**
 * Get current URL query parameters.
 * It returns array of params in form 'param=value' if there are any params supplied in the given url. If there are none it returns empty array
 *
 * @param {string} baseUrl
 *
 * @returns {array} array with params or empty
 */
function getCurrentQueryParams(baseUrl) {
    if(baseUrl.indexOf('?') > 0) {
        return baseUrl.slice(baseUrl.indexOf('?') + 1).split('&');
    }

    return [];
}

/**
 * Get navigation buttons.
 *
 * @param {int} start
 * @param {numFound} start
 *
 * @returns {string}
 */
function getNavigationButtons(start, numFound) {
    var buttons = "";

    if(start > 0) {
        buttons += '<input type="button" id="tx-dlf-search-in-document-button-previous" class="button-previous" onclick="previousResultPage();" />';
    }

    if(numFound > (start + 20)) {
        buttons += '<input type="button" id="tx-dlf-search-in-document-button-next" class="button-next" onclick="nextResultPage();" />';
    }
    return buttons;
}

/**
 * Get current page.
 *
 * @returns {int}
 */
function getCurrentPage() {
    var page = 1;
    var baseUrl = getBaseUrl(" ");
    var queryParams = getCurrentQueryParams(baseUrl);
    var pageFound = false;

    for(var i = 0; i < queryParams.length; i++) {
        var queryParam = queryParams[i].split('=');

        if(decodeURIComponent(queryParam[0]) === $("input[id='tx-dlf-search-in-document-page']").attr('name')) {
            page = parseInt(queryParam[1], 10);
            pageFound = true;
        }
    }

    if (!pageFound) {
        var url = baseUrl.split('/');
        page = parseInt(url.pop(), 10);
    }
    return page;
}

/**
 * Add highlight to image.
 *
 * @param {array} data
 *
 * @returns void
 */
function addImageHighlight(data) {
    var page = getCurrentPage();

    if (typeof tx_dlf_viewer !== 'undefined' && tx_dlf_viewer.map != null) { // eslint-disable-line camelcase
        var highlights = [];

        data['documents'].forEach(function (element, i) {
            if(page <= element['page'] && element['page'] < page + tx_dlf_viewer.countPages()) { // eslint-disable-line camelcase
                if (element['highlight'].length > 0) {
                    highlights.push(getHighlights(element['highlight']));
                }
                addHighlightEffect(element['highlight']);
            }
        });

        tx_dlf_viewer.displayHighlightWord(encodeURIComponent(highlights.join(';'))); // eslint-disable-line camelcase
    } else {
        setTimeout(addImageHighlight, 500, data);
    }
}

/**
 * Trigger search for document loaded from hit list.
 *
 * @returns void
 */
function triggerSearchAfterHitLoad() {
    var queryParams = getCurrentQueryParams(getBaseUrl(" "));
    var searchedQueryParam = $("input[id='tx-dlf-search-in-document-highlight-word']").attr('name');

    for(var i = 0; i < queryParams.length; i++) {
        var queryParam = queryParams[i].split('=');

        if(searchedQueryParam && decodeURIComponent(queryParam[0]).indexOf(searchedQueryParam) !== -1) {
            $("input[id='tx-dlf-search-in-document-query']").val(decodeURIComponent(queryParam[1]));
            $("#tx-dlf-search-in-document-form").submit();
            break;
        }
    }
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
                middleware: "dlf/search-in-document",
                q: $( "input[id='tx-dlf-search-in-document-query']" ).val(),
                uid: $( "input[id='tx-dlf-search-in-document-id']" ).val(),
                pid: $( "input[id='tx-dlf-search-in-document-pid']" ).val(),
                start: $( "input[id='tx-dlf-search-in-document-start']" ).val(),
                encrypted: $( "input[id='tx-dlf-search-in-document-encrypted']" ).val(),
            },
            function(data) {
                var resultItems = [];
                var resultList = '<div class="results-active-indicator"></div><ul>';
                var start = $( "input[id='tx-dlf-search-in-document-start']" ).val();
                if (data['numFound'] > 0) {
                    data['documents'].forEach(function (element, i) {
                        if (start < 0) {
                            start = i;
                        }
                        if (element['snippet'].length > 0) {
                            resultItems[element['page']] = '<span class="structure">'
                                + $('#tx-dlf-search-in-document-label-page').text() + ' ' + element['page']
                                + '</span><br />'
                                + '<span class="textsnippet">'
                                + '<a href=\"' + element['url'] + '\">' + element['snippet'] + '</a>'
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

                    addImageHighlight(data);
                } else {
                    resultList += '<li class="noresult"></li>';
                }
                resultList += '</ul>';
                resultList += getNavigationButtons(start, data['numFound']);
                $('#tx-dlf-search-in-document-results').html(resultList);
                $('.noresult').text($('#tx-dlf-search-in-document-label-noresult').text());
                $('.button-previous').attr('value', $('#tx-dlf-search-in-document-label-previous').text());
                $('.button-next').attr('value', $('#tx-dlf-search-in-document-label-next').text());
            },
            "json"
        )
        .done(function (data) {
            $('#tx-dlf-search-in-document-loading').hide();
            $('#tx-dlf-search-in-document-clearing').show();
        });
    });

     // clearing button
     $('#tx-dlf-search-in-document-clearing').click(function() {
        $('#tx-dlf-search-in-document-results ul').remove();
        $('.results-active-indicator').remove();
        $('#tx-dlf-search-in-document-query').val('');
    });

    triggerSearchAfterHitLoad();
});
