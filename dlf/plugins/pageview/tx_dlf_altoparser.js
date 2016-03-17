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

/**
 * @namespace
 * @type {{}}
 */
var dlfAltoParser = {};

/**
 * Parse from an alto element a OpenLayers feature object. This function does reproduce the parsing hierarchy of this parser.
 * @param {Element} node
 * @return {ol.Feature}
 * @private
 */
dlfAltoParser.parseAltoFeature_ = function(node) {
    var type = node.nodeName.toLowerCase(),
        feature;

    // first parse the node as feature
    if (type === 'printspace' || type === 'textblock' || type === 'textline' || node.hasAttribute('WIDTH')) {
        feature = dlfAltoParser.parseFeatureWithGeometry_(node);
    } else {
        var feature = new ol.Feature();
        feature.setProperties({'type': node.nodeName.toLowerCase()});
    };

    // parse child nodes
    if (type === 'page') {
        feature.setProperties({'printspace': dlfAltoParser.parsePrintSpaceFeature_(node)});
    } else if (type === 'printspace') {
        feature.setProperties({'textblocks': dlfAltoParser.parseTextBlockFeatures_(node)});
    } else if (type === 'textblock') {
        feature.setProperties({'textlines': dlfAltoParser.parseTextLineFeatures_(node)});
    }

    return feature;
};

/**
 * @param {XMLDocument|string} document
 * @return {Array.<ol.Feature>}
 */
dlfAltoParser.parseFeatures = function(document) {
    var parsedDoc = dlfAltoParser.parseXML_(document),
        pageFeatures = [],
        pageElements = $(parsedDoc).find('Page');

    for (var i = 0; i < pageElements.length; i++) {
        // parse page feature
        var feature = dlfAltoParser.parseAltoFeature_(pageElements[i]);
        pageFeatures.push(feature);
    };

    return pageFeatures;
};

/**
 * Parse from an alto element a OpenLayers feature object
 * @param {Element} node
 * @return {ol.Feature}
 * @private
 */
dlfAltoParser.parseFeatureWithGeometry_ = function(node) {
    var geometry = dlfAltoParser.parseGeometry_(node),
        width = parseInt(node.getAttribute("WIDTH")),
        height = parseInt(node.getAttribute("HEIGHT")),
        hpos = parseInt(node.getAttribute("HPOS")),
        vpos = parseInt(node.getAttribute("VPOS")),
        type = node.nodeName.toLowerCase(),
        feature = new ol.Feature(geometry);

    feature.setProperties({
        'type':type,
        'width': width,
        'height': height,
        'hpos': hpos,
        'vpos': vpos
    });

    return feature;
};

/**
 * Parse from an alto element a OpenLayers geometry object
 * @param {Element} node
 * @return {ol.geom.Polygon|undefined}
 * @private
 */
dlfAltoParser.parseGeometry_ = function(node) {
    var width = parseInt(node.getAttribute("WIDTH")),
        height = parseInt(node.getAttribute("HEIGHT")),
        x1 = parseInt(node.getAttribute("HPOS")),
        y1 = parseInt(node.getAttribute("VPOS")),
        x2 = x1 + width,
        y2 = y1 + height,
        coordinates = [[[x1, y1], [x2, y1], [x2, y2], [x1, y2], [x1, y1]]];

    if (isNaN(width) || isNaN(height))
        return undefined;
    return new ol.geom.Polygon(coordinates);
};

/**
 * @param {Element} node
 * @return {ol.Feature|undefined}
 * @private
 */
dlfAltoParser.parsePrintSpaceFeature_ = function(node) {
    var printspace = $(node).find('PrintSpace');

    if (printspace.length === 0)
        return;
    return dlfAltoParser.parseAltoFeature_(printspace[0]);
};

/**
 * @param {Element} node
 * @return {Array.<ol.Feature>}
 * @private
 */
dlfAltoParser.parseTextBlockFeatures_ = function(node) {
    var textblockElements = $(node).find('TextBlock'),
        textblockFeatures = [];

    for (var i = 0; i < textblockElements.length; i++) {
        var feature = dlfAltoParser.parseAltoFeature_(textblockElements[i]),
            textlines = feature.get('textlines'),
            fulltext = '';

        // aggregated fulltexts
        for (var j = 0; j < textlines.length; j++) {
            fulltext += textlines[j].get('fulltext') + '\n';
        };
        feature.setProperties({'fulltext':fulltext});

        textblockFeatures.push(feature);
    };

    return textblockFeatures;
};

/**
 * @param {Element} node
 * @return {Array.<ol.Feature>}
 * @private
 */
dlfAltoParser.parseTextLineFeatures_ = function(node) {
    var textlineElements = $(node).find('TextLine'),
        textlineFeatures = [];

    for (var i = 0; i < textlineElements.length; i++) {
        var feature = dlfAltoParser.parseAltoFeature_(textlineElements[i]),
            fulltextElements = $(textlineElements[i]).children(),
            fulltext = '';

        // parse fulltexts
        for (var j = 0; j < fulltextElements.length; j++) {
            switch (fulltextElements[j].nodeName.toLowerCase()) {
                case 'string':
                    fulltext += fulltextElements[j].getAttribute('CONTENT');
                    break;
                case 'sp':
                    fulltext += ' ';
                    break;
                case 'hyp':
                    fulltext += '-';
                    break;
                default:
                    fulltext += '';
            };
        };
        feature.setProperties({'fulltext':fulltext});

        textlineFeatures.push(feature);
    };

    return textlineFeatures;
};



/**
 *
 * @param {XMLDocument|string}
 * @return {XMLDocument}
 * @private
 */
dlfAltoParser.parseXML_ = function(document) {
    if (typeof document === 'string' || document instanceof String) {
        return $.parseXML(document);
    };
    return document;
};