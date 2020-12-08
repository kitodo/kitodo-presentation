/**
 * Base namespace for utility functions used by the dlf module.
 *
 * @const
 */
var dlfFullTextUtils = dlfFullTextUtils || {};

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
 * @param {string} url
 * @param {Object} image
 * @param {number=} optOffset
 * @return {ol.Feature|undefined}
 * @static
 */
dlfFullTextUtils.fetchFullTextDataFromServer = function(url, image, optOffset){
    // fetch data from server
    var request = $.ajax({
        url,
        async: false
    });

    var offset = dlfUtils.exists(optOffset) ? optOffset : undefined;

    return dlfFullTextUtils.parseAltoData(image, offset, request);
};

/**
 * Method parses ALTO data
 * @param {Object} image
 * @param {number=} offset
 * @param {Object} request
 * @return {ol.Feature|undefined}
 * @static
 */
dlfFullTextUtils.parseAltoData = function(image, offset, request){
    var parser = new dlfAltoParser(image, undefined, undefined, offset),
      fulltextCoordinates = request.responseXML ? parser.parseFeatures(request.responseXML) :
            request.responseText ? parser.parseFeatures(request.responseText) : [];

    return fulltextCoordinates.length > 0 ? fulltextCoordinates[0] : undefined;
};
