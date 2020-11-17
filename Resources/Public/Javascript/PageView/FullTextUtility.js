'use strict';

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
 * Base namespace for full text utility functions used by the dlf module.
 *
 * @const
 */
var dlfFullTextUtils = dlfFullTextUtils || {};

/**
 * Handle TextBlock elements
 * @param {Object} hoverSourceTextblock_
 * @param {Object} selectSource_
 * @þaram {ol.Feature} textblockFeature
 * @static
 */
dlfFullTextUtils.handleTextBlockElements = function(hoverSourceTextblock, selectSource, textblockFeature) {
    var activeSelectTextBlockEl_ = this.getFeature(selectSource),
        activeHoverTextBlockEl_ = this.getFeature(hoverSourceTextblock),
        isFeatureEqualSelectFeature_ = this.isFeatureEqual(activeSelectTextBlockEl_, textblockFeature),
        isFeatureEqualToOldHoverFeature_ = this.isFeatureEqual(activeHoverTextBlockEl_, textblockFeature);

    if (!isFeatureEqualToOldHoverFeature_ && !isFeatureEqualSelectFeature_) {
        // remove old textblock hover features
        hoverSourceTextblock.clear();

        if (textblockFeature) {
            // add textblock feature to hover
            hoverSourceTextblock.addFeature(textblockFeature);
        }
    }
};

/**
 * Handle TextLine elements
 * @param {Object} hoverSourceTextline
 * @þaram {ol.Feature} textlineFeature
 * @static
 */
dlfFullTextUtils.handleTextLineElements = function(hoverSourceTextline, textlineFeature) {
    var activeHoverTextBlockEl_ = this.getFeature(hoverSourceTextline),
        isFeatureEqualToOldHoverFeature_ = this.isFeatureEqual(activeHoverTextBlockEl_, textlineFeature);

    if (!isFeatureEqualToOldHoverFeature_) {

        if (activeHoverTextBlockEl_) {
            // remove highlight effect on fulltext view
            var oldTargetElem = $('#' + activeHoverTextBlockEl_.getId());

            if (oldTargetElem.hasClass('highlight') ) {
                oldTargetElem.removeClass('highlight');
            }

            // remove old textline hover features
            hoverSourceTextline.clear();
        }

        this.highlightFullText(textlineFeature);
    }
};

/**
 * Highlight full text
 * @param {Object} hoverSourceTextline
 * @param {ol.Feature} textlineFeature
 */
dlfFullTextUtils.highlightFullText = function(hoverSourceTextline, textlineFeature) {
    if (textlineFeature) {
        // add highlight effect to fulltext view
        var targetElem = $('#' + textlineFeature.getId());

        if (targetElem.length > 0 && !targetElem.hasClass('highlight')) {
            targetElem.addClass('highlight');
            $('#tx-dlf-fulltextselection').scrollTo(targetElem, 50);
            hoverSourceTextline.addFeature(textlineFeature);
        }
    }
};

/**
 * Get feature from given source
 * @param {Object} source
 */
dlfFullTextUtils.getFeature = function(source) {
    return source.getFeatures().length > 0 ? source.getFeatures()[0] : undefined;
};

/**
 * Check if given feature element is equal to other feature
 * @param {ol.Feature} element
 * @þaram {ol.Feature} feature
 */
dlfFullTextUtils.isFeatureEqual = function(element, feature) {
    return element !== undefined && feature !== undefined && element.getId() === feature.getId();
};

/**
 * Method fetches the fulltext data from the server
 * @param {string} url
 * @param {Object} image
 * @param {number=} optOffset
 * @return {ol.Feature|undefined}
 * @static
 */
dlfFullTextUtils.fetchFullTextDataFromServer = function(url, image, optOffset) {
    // fetch data from server
    var request = $.ajax({
        url,
        async: false
    });

    var fulltextCoordinates = this.parseAltoData(request, image, optOffset);

    return fulltextCoordinates.length > 0 ? fulltextCoordinates[0] : undefined;
};

/**
 * Method parses ALTO data from request response
 * @param {string} url
 * @param {Object} image
 * @param {number=} optOffset
 * @return {Array.<ol.Feature>}
 */
dlfFullTextUtils.parseAltoData = function(request, image, optOffset) {
    var offset = dlfUtils.exists(optOffset) ? optOffset : undefined,
      parser = new dlfAltoParser(image, undefined, undefined, offset);

      return request.responseXML ? parser.parseFeatures(request.responseXML) :
            request.responseText ? parser.parseFeatures(request.responseText) : [];
};