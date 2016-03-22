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
 * @constructor
 * @param {Object=} opt_imageObj
 * @param {number=} opt_width
 * @param {number=} opt_height
 * @param {number=} opt_offset
 */
var dlfAltoParser = function(opt_imageObj, opt_width, opt_height, opt_offset) {

    /**
     * @type {Object|undefined}
     * @private
     */
    this.image_ = dlfUtils.exists(opt_imageObj) ? opt_imageObj : undefined;

    /**
     * @type {number|undefined}
     * @private
     */
    this.width_ = dlfUtils.exists(opt_width) ? opt_width : undefined;

    /**
     * @type {number|undefined}
     * @private
     */
    this.height_ = dlfUtils.exists(opt_height) ? opt_height : undefined;

    /**
     * @type {number|undefined}
     * @private
     */
    this.offset_ = dlfUtils.exists(opt_offset) ? opt_offset : undefined;
};

/**
 * @param {number} width
 * @param {number} height
 * @param {number} hpos
 * @param {number} vpos
 * @private
 */
dlfAltoParser.prototype.generateId_ = function(width, height, hpos, vpos) {
    return '' + width + '_' + height + '_' + hpos + '_' + vpos;
};

/**
 * Parse from an alto element a OpenLayers feature object. This function does reproduce the parsing hierarchy of this parser.
 * @param {Element} node
 * @return {ol.Feature}
 * @private
 */
dlfAltoParser.prototype.parseAltoFeature_ = function(node) {
    var type = node.nodeName.toLowerCase(),
        feature;

    // first parse the node as feature
    if (type === 'printspace' || type === 'textblock' || type === 'textline' || node.hasAttribute('WIDTH')) {
        feature = this.parseFeatureWithGeometry_(node);
    } else {
        var feature = new ol.Feature();
        feature.setProperties({'type': node.nodeName.toLowerCase()});
    };

    // parse child nodes
    if (type === 'page') {
        // try to update the general width and height for rescaling
        var width = feature.get('width'),
            height = feature.get('height');

        if ((dlfUtils.exists(width) && dlfUtils.exists(height)) && (!dlfUtils.exists(this.width_) && !dlfUtils.exists(this.width_))) {
            this.width_ = width;
            this.height_ = height;
        }

        // add child features
        feature.setProperties({'printspace': this.parsePrintSpaceFeature_(node)});
    } else if (type === 'printspace') {
        // try to update the general width and height for rescaling
        var width = feature.get('width'),
            height = feature.get('height');

        if ((dlfUtils.exists(width) && dlfUtils.exists(height)) && (!dlfUtils.exists(this.width_) && !dlfUtils.exists(this.width_))) {
            this.width_ = width;
            this.height_ = height;
        }

        // add child features
        feature.setProperties({'textblocks': this.parseTextBlockFeatures_(node)});
    } else if (type === 'textblock') {
        feature.setProperties({'textlines': this.parseTextLineFeatures_(node)});
    } else if (type === 'textline') {
        feature.setProperties({'content': this.parseContentFeatures_(node)});
    }

    return feature;
};

/**
 * @param {XMLDocument|string} document
 * @return {Array.<ol.Feature>}
 */
dlfAltoParser.prototype.parseFeatures = function(document) {
    var parsedDoc = this.parseXML_(document),
        pageFeatures = [],
        pageElements = $(parsedDoc).find('Page');

    for (var i = 0; i < pageElements.length; i++) {
        // parse page feature
        var feature = this.parseAltoFeature_(pageElements[i]);

        /**
         * Attach function for a better access of of string elements
         * @return {Array}
         */
        feature.getStringFeatures = function() {
            var textblockFeatures = this.get('printspace').get('textblocks'),
                stringFeatures = [];

            textblockFeatures.forEach(function(textblock) {

                if (textblock !== undefined && textblock.get('textlines').length > 0) {

                    textblock.get('textlines').forEach(function(textline) {

                        if (textline !== undefined && textline.get('content').length > 0) {

                            textline.get('content').forEach(function(content) {

                                if (content !== undefined && content.get('type') === 'string') {
                                    stringFeatures.push(content);
                                }

                            });
                        }

                    });

                }

            });

            return stringFeatures;
        };

        /**
         * Add function for getting the text blocks
         * @return {Array}
         */
        feature.getTextblocks = function() {
            if (this.get('printspace') !== undefined && this.get('printspace').get('textblocks'))
                return this.get('printspace').get('textblocks')
            return [];
        };

        /**
         * Adding function for getting the textline
         * @return {Array}
         */
        feature.getTextlines = function() {
            var textlines = [];
            this.getTextblocks().forEach(function(textblock) {
                textlines = textlines.concat(textblock.get('textlines'));
            });
            return textlines;
        };

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
dlfAltoParser.prototype.parseFeatureWithGeometry_ = function(node) {
    var geometry = this.parseGeometry_(node),
        width = parseInt(node.getAttribute("WIDTH")),
        height = parseInt(node.getAttribute("HEIGHT")),
        hpos = parseInt(node.getAttribute("HPOS")),
        vpos = parseInt(node.getAttribute("VPOS")),
        type = node.nodeName.toLowerCase(),
        id = this.generateId_(width, height, hpos, vpos),
        feature = new ol.Feature(geometry);

    feature.setId(id);
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
dlfAltoParser.prototype.parseGeometry_ = function(node) {
    var width = parseInt(node.getAttribute("WIDTH")),
        height = parseInt(node.getAttribute("HEIGHT")),
        x1 = parseInt(node.getAttribute("HPOS")),
        y1 = parseInt(node.getAttribute("VPOS")),
        x2 = x1 + width,
        y2 = y1 + height,
        coordinatesWithoutScale = [[[x1, y1], [x2, y1], [x2, y2], [x1, y2], [x1, y1]]];

    if (isNaN(width) || isNaN(height))
        return undefined;

    // return geometry without rescale
    if (!dlfUtils.exists(this.image_) || !dlfUtils.exists(this.width_))
        return new ol.geom.Polygon(coordinatesWithoutScale);

    // rescale coordinates
    var scale = this.image_.width / this.width_,
        displayedImageHeight = this.image_.height,
        offset = dlfUtils.exists(this.offset_) ? this.offset_ : 0,
        coordinatesRescale = [];

    for (var i = 0; i < coordinatesWithoutScale[0].length; i++) {
        coordinatesRescale.push([offset + ( scale * coordinatesWithoutScale[0][i][0]),
            displayedImageHeight - (scale * coordinatesWithoutScale[0][i][1])]);
    };

    return new ol.geom.Polygon([coordinatesRescale]);
};

/**
 * @param {Element} node
 * @return {ol.Feature|undefined}
 * @private
 */
dlfAltoParser.prototype.parsePrintSpaceFeature_ = function(node) {
    var printspace = $(node).find('PrintSpace');

    if (printspace.length === 0)
        return;
    return this.parseAltoFeature_(printspace[0]);
};

/**
 * @param {Element} node
 * @return {Array.<ol.Feature>}
 * @private
 */
dlfAltoParser.prototype.parseTextBlockFeatures_ = function(node) {
    var textblockElements = $(node).find('TextBlock'),
        textblockFeatures = [];

    for (var i = 0; i < textblockElements.length; i++) {
        var feature = this.parseAltoFeature_(textblockElements[i]),
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
dlfAltoParser.prototype.parseTextLineFeatures_ = function(node) {
    var textlineElements = $(node).find('TextLine'),
        textlineFeatures = [];

    for (var i = 0; i < textlineElements.length; i++) {
        var feature = this.parseAltoFeature_(textlineElements[i]),
            fulltextElements = feature.get('content'),
            fulltext = '';

        // parse fulltexts
        for (var j = 0; j < fulltextElements.length; j++) {
            fulltext += fulltextElements[j].get('fulltext');
        };
        feature.setProperties({'fulltext':fulltext});

        textlineFeatures.push(feature);
    };

    return textlineFeatures;
};

/**
 * @param {Element} node
 * @return {Array.<ol.Feature>}
 * @private
 */
dlfAltoParser.prototype.parseContentFeatures_ = function(node) {
    var textlineContentElements = $(node).children(),
        textlineContentFeatures = [];

    for (var i = 0; i < textlineContentElements.length; i++) {
        var feature = this.parseFeatureWithGeometry_(textlineContentElements[i]),
            fulltext = '';

        // parse fulltexts
        switch (textlineContentElements[i].nodeName.toLowerCase()) {
            case 'string':
                fulltext = textlineContentElements[i].getAttribute('CONTENT');
                break;
            case 'sp':
                fulltext = ' ';
                break;
            case 'hyp':
                fulltext = '-';
                break;
            default:
                fulltext = '';
        };
        feature.setProperties({'fulltext':fulltext});

        textlineContentFeatures.push(feature);
    };

    return textlineContentFeatures;
};



/**
 *
 * @param {XMLDocument|string}
 * @return {XMLDocument}
 * @private
 */
dlfAltoParser.prototype.parseXML_ = function(document) {
    if (typeof document === 'string' || document instanceof String) {
        return $.parseXML(document);
    };
    return document;
};