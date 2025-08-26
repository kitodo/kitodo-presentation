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

/* global dlfTeiParser */

 /**
 * Base namespace for utility functions used by the dlf module.
 *
 * @const
 */
var dlfFullTextUtils;
dlfFullTextUtils = dlfFullTextUtils || {};

/**
 * Get feature from given source
 * @param {Object} source
 * @return {ol.Feature|undefined}
 * @static
 */
dlfFullTextUtils.getFeature = function(source){
    return source.getFeatures().length > 0 ? source.getFeatures()[0] : undefined;
};

/**
 * Check if given element is equal to given feature
 * @param {Object} element
 * @param {Object} feature
 * @return {boolean}
 * @static
 */
dlfFullTextUtils.isFeatureEqual = function(element, feature){
    return element !== undefined && feature !== undefined && element.getId() === feature.getId();
};

/**
 * Method fetches the fulltext data from the server
 * @param {object} fulltext
 * @param {object} image
 * @param {number=} optOffset
 * @returns {FullTextFeature | undefined}
 * @static
 */
dlfFullTextUtils.fetchFullTextDataFromServer = function(fulltext, image, optOffset) {
    var result = new $.Deferred();
    var url = fulltext.url;
    $.ajax({ url }).done(function (data, status, jqXHR) {
        try {
            var fulltextResult;
            if(fulltext.mimetype === 'application/tei+xml') {
              const params = new URLSearchParams(window.location.search);
              const pageId = params.get("tx_dlf[page]");
              fulltextResult = dlfFullTextUtils.parseTeiData(pageId, jqXHR);
            } else {
              fulltextResult = dlfFullTextUtils.parseAltoData(image, optOffset, jqXHR);
            }

            if (data === undefined) {
                result.reject();
            } else {
                result.resolve(fulltextResult);
            }
        } catch (e) {
            console.error(e); // eslint-disable-line no-console
            result.reject();
        }
    });

    return result;
};

/**
 * Method parses ALTO data
 * @param {Object} image
 * @param {number=} offset
 * @param {Object} request
 * @return {FullTextFeature | undefined}
 * @static
 */
dlfFullTextUtils.parseAltoData = function(image, offset, request){
    var parser = new dlfAltoParser(image, undefined, undefined, offset),
      fulltextCoordinates = request.responseXML ? parser.parseFeatures(request.responseXML) :
            request.responseText ? parser.parseFeatures(request.responseText) : [];

    return fulltextCoordinates.length > 0 ? fulltextCoordinates[0] : undefined;
};

/**
 * Method parses TEI data
 * @param {string=} pageId
 * @param {object} request
 * @returns {object}
 * @static
 */
dlfFullTextUtils.parseTeiData = function(pageId, request){
  const parser = new dlfTeiParser(pageId);
  return request.responseXML ? parser.parse(request.responseXML) :
      request.responseText ? parser.parse(request.responseText) : [];
};
