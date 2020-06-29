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
 * This is necessary to support the scrolling of the element into the viewport
 * in case of text hover on the map.
 *
 * @param elem
 * @param speed
 * @returns {jQuery}
 */
jQuery.fn.scrollTo = function(elem, speed) {
    var manualOffsetTop = $(elem).parent().height() / 2;
    $(this).animate({
        scrollTop:  $(this).scrollTop() - $(this).offset().top + $(elem).offset().top - manualOffsetTop
    }, speed === undefined ? 1000 : speed);
    return this;
};

/**
 * Encapsulates especially the fulltext behavior
 * @constructor
 * @param {string} fulltextUrl
 */
var dlfViewerTeiFullTextControl = function(fulltextUrl) {

    /**
     * @type {string}
     * @private
     */
    this.url = fulltextUrl;

    /**
     * @type {Object}
     * @private
     */
    this.dic = $('#tx-dlf-tools-fulltext').length > 0 && $('#tx-dlf-tools-fulltext').attr('data-dic') ?
        dlfUtils.parseDataDic($('#tx-dlf-tools-fulltext')) :
        {'fulltext-on':'Activate Fulltext','fulltext-off':'Deactivate Fulltext'};

    /**
     * @type {ol.Feature|undefined}
     * @private
     */
    this.fulltextData_ = undefined;

    // add active / deactive behavior in case of click on control
    var anchorEl = $('#tx-dlf-tools-fulltext');
    if (anchorEl.length > 0){
        var toogleFulltext = $.proxy(function(event) {
            event.preventDefault();

            if ($(event.target).hasClass('active')) {
                this.deactivate();
                return;
            }

            this.activate();
        }, this);


        anchorEl.on('click', toogleFulltext);
        anchorEl.on('touchstart', toogleFulltext);
    }

    // set initial title of fulltext element
    $("#tx-dlf-tools-fulltext")
        .text(this.dic['fulltext-on'])
        .attr('title', this.dic['fulltext-on']);

    // if fulltext is activated via cookie than run activation methode
    if (dlfUtils.getCookie("tx-dlf-pageview-fulltext-select") === 'enabled') {
        // activate the fulltext behavior
        this.activate(anchorEl);
    }

};

/**
 * Activate Fulltext Features
 */
dlfViewerTeiFullTextControl.prototype.activate = function() {

    var controlEl = $('#tx-dlf-tools-fulltext');

    // if the activate method is called for the first time fetch
    // fulltext data from server
    if (this.fulltextData_ === undefined)  {
        this.fulltextData_ = dlfViewerTeiFullTextControl.fetchFulltextDataFromServer(this.url);
    }

    // now activate the fulltext overlay and map behavior
    this.enableFulltextSelect();
    dlfUtils.setCookie("tx-dlf-pageview-fulltext-select", 'enabled');
    $(controlEl).addClass('active');

    // trigger event
    $(this).trigger("activate-fulltext", this);
};

/**
 * Activate Fulltext Features
 */
dlfViewerTeiFullTextControl.prototype.deactivate = function() {

    var controlEl = $('#tx-dlf-tools-fulltext');

    // deactivate fulltext
    this.disableFulltextSelect();
    dlfUtils.setCookie("tx-dlf-pageview-fulltext-select", 'disabled');
    $(controlEl).removeClass('active');

    // trigger event
    $(this).trigger("deactivate-fulltext", this);
};

/**
 * Disable Fulltext Features
 *
 * @return void
 */
dlfViewerTeiFullTextControl.prototype.disableFulltextSelect = function() {

    var className = 'fulltext-visible';
    $("#tx-dlf-tools-fulltext").removeClass(className)
        .text(this.dic['fulltext-on'])
        .attr('title', this.dic['fulltext-on']);

    $('#tx-dlf-fulltextselection').removeClass(className);
    $('#tx-dlf-fulltextselection').hide();
    $('body').removeClass(className);

};

/**
 * Activate Fulltext Features
 * @param {Array.<ol.Feature>} textBlockFeatures
 * @þaram {Array.<ol.Feature>} textLineFeatures
 */
dlfViewerTeiFullTextControl.prototype.enableFulltextSelect = function(textBlockFeatures, textLineFeatures) {

    // show fulltext container
    var className = 'fulltext-visible';
    $("#tx-dlf-tools-fulltext").addClass(className)
      .text(this.dic['fulltext-off'])
      .attr('title', this.dic['fulltext-off']);

    $('#tx-dlf-fulltextselection').addClass(className);
    $('#tx-dlf-fulltextselection').show();
    $('body').addClass(className);
};

/**
 * Method fetches the fulltext data from the server
 * @param {string} url
 * @return {bol}
 * @static
 */
dlfViewerTeiFullTextControl.fetchFulltextDataFromServer = function(url){

    // maybe doing some stuff with the TEI
    var parser = new dlfTeiParser();

    var textcontainer = $('#tx-dlf-fulltextselection').height();

    $('#tx-dlf-fulltextselection').append('<object data="' + url + '" width="100%" height="' + (textcontainer - 30) + 'px" type="text/xml"></object>');

    return true;
};