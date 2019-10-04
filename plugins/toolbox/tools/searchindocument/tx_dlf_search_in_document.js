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
  * @param rows
  * @returns void
  */
function nextResultPage(rows) {
    var currentstart = $("#tx-dlf-search-in-document-form input[name='tx_dlf[start]']").val();
    var newstart = parseInt(currentstart) + rows;
    $("#tx-dlf-search-in-document-form input[name='tx_dlf[start]']").val(newstart);
    $('#tx-dlf-search-in-document-form').submit();
}

/**
 * This function decreases the start parameter of the search form and submits
 * the form.
 *
 * @param rows
 * @returns void
 */
function previousResultPage(rows) {
    var currentstart = $("#tx-dlf-search-in-document-form input[name='tx_dlf[start]']").val();
    currentstart = parseInt(currentstart);
    var newstart = (currentstart > rows) ? (currentstart - rows) : 0;
    $("#tx-dlf-search-in-document-form input[name='tx_dlf[start]']").val(newstart);
    $('#tx-dlf-search-in-document-form').submit();
}

/**
 * This function resets the start parameter on new queries.
 *
 * @returns void
 */
function resetStart() {
    $("#tx-dlf-search-in-document-form input[name='tx_dlf[start]']").val(0);
}

$(document).ready(function() {
    $("#tx-dlf-search-in-document-form").submit(function( event ) {
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
                eID: "tx_dlf_search_in_document_eid",
                q: $( "input[name='tx_dlf[query]']" ).val(),
                uid: $( "input[name='tx_dlf[id]']" ).val(),
                start: $( "input[name='tx_dlf[start]']" ).val(),
                encrypted: $( "input[name='tx_dlf[encrypted]']" ).val(),
                hashed: $( "input[name='tx_dlf[hash]']" ).val(),
            },
            function(data) {
              var resultItems = [];
              var resultList = '<div class="results-active-indicator"></div><ul>';
              if (data.error) {
                  resultList += '<li>' + data.error + '</li>';
              } else {
                  for (var i=0; i < data.response.docs.length; i++) {

                      var linkCurrent = $(location).attr('href');
                      var linkBase = linkCurrent.substring(0, linkCurrent.indexOf('?'));
                      var linkParams = linkCurrent.substring(linkBase.length + 1, linkCurrent.length);
                      var linkId = linkParams.match(/id=(\d)*/g);

                      if (linkId) {
                        linkParams = linkId + '&';
                      } else {
                        linkParams = '&';
                      }

                      var searchHit = data.highlighting[data.response.docs[i].id].fulltext.toString();
                      searchHit = searchHit.substring(searchHit.indexOf('<em>')+4,searchHit.indexOf('</em>'));

                      var newlink = linkBase + '?' + (linkParams
                      + 'tx_dlf[id]=' + data.response.docs[i].uid
                      + '&tx_dlf[highlight_word]=' + encodeURIComponent(searchHit)
                      + '&tx_dlf[page]=' + (data.response.docs[i].page));

                      if (data.highlighting[data.response.docs[i].id].fulltext) {
                          resultItems[data.response.docs[i].page] = '<span class="structure">' + $('#tx-dlf-search-in-document-label-page').text() + ' ' + data.response.docs[i].page + '</span><br /><span ="textsnippet"><a href=\"' + newlink + '\">' + data.highlighting[data.response.docs[i].id].fulltext + '</a></span>';
                      }
                  }
                  // sort by page as this cannot be done with current solr schema
                  resultItems.sort(function(a, b){return a-b;});
                  resultItems.forEach(function(item, index){
                      resultList += '<li>' + item + '</li>';
                  });
              }
              resultList += '</ul>';
              if (parseInt(data.response.start) > 0) {
                  resultList += '<input type="button" id="tx-dlf-search-in-document-button-previous" class="button-previous" onclick="previousResultPage(' + data.responseHeader.params.rows + ');" value="' + $('#tx-dlf-search-in-document-label-previous').text() + '" />';
              }
              if (parseInt(data.response.numFound) > (parseInt(data.response.start) + parseInt(data.responseHeader.params.rows))) {
                  resultList += '<input type="button" id="tx-dlf-search-in-document-button-next" class="button-next" onclick="nextResultPage(' + data.responseHeader.params.rows + ');" value="' + $('#tx-dlf-search-in-document-label-next').text() + '" />';
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
