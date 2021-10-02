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
 * @constructor
 * @param {Object=} opt_imageObj
 * @param {number=} opt_width
 * @param {number=} opt_height
 * @param {number=} opt_offset
 */
var DlfIiifAnnotationParser = function(opt_imageObj, opt_width, opt_height, opt_offset) {

    // get width and height either from image info.json or from canvas information

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
DlfIiifAnnotationParser.prototype.generateId_ = function(width, height, hpos, vpos) {
    var heigt_ = isNaN(height) ? '0' : height;
    return 'anno_' + width + '_' + heigt_ + '_' + hpos + '_' + vpos;
};

/**
 * Create an OpenLayers Feature from a IIIF Annotation
 * @param {Object} annotation
 * @return {ol.Feature}
 * @private
 */
DlfIiifAnnotationParser.prototype.parseAnnotation = function(annotation) {
    var geometry = this.parseGeometry(annotation),
        xywh = this.getXYWHForAnnotation(annotation),
        id = this.generateId_(xywh.width, xywh.height, xywh.x1, xywh.y1),
        feature = new ol.Feature(geometry);

    feature.setId(id);
    feature.setProperties({
        'type': 'annotation',
        'width': xywh.width,
        'height': xywh.height,
        'x1': xywh.x1,
        'y1': xywh.y1,
        'x2': xywh.x2,
        'y2': xywh.y2,
        'content': annotation.resource.chars
    });

    return feature;
};

DlfIiifAnnotationParser.prototype.parseAnnotationList = function(annotationList, currentCanvas) {

    var minX, maxX, minY, maxY, annotationFeatures = [];

    for (var i = 0; i < annotationList.resources.length; i++) {

        var annotation = annotationList.resources[i];

        var onCanvas = DlfIiifAnnotationParser.getTargetIdentifierWithoutFragment(annotation.on);

        if (currentCanvas !== onCanvas) {
            continue;
        }

        var feature = this.parseAnnotation(annotation);

        // Determine the dimension of the AnnotationList
        minX = minX === undefined ? feature.get('x1') : minX > feature.get('x1') ? feature.get('x1') : minX;
        maxX = maxX === undefined ? feature.get('x2') : maxX < feature.get('x2') ? feature.get('x2') : maxX;
        minY = minY === undefined ? feature.get('y1') : minY > feature.get('y1') ? feature.get('y1') : minY;
        maxY = maxY === undefined ? feature.get('y2') : maxY < feature.get('y2') ? feature.get('y2') : maxY;

        annotationFeatures.push(feature);
    }

    var width = maxX - minX,
        height = maxY - minY,
        listCoordinatesWithoutScale = [[[minX, minY], [maxX, minY], [maxX, maxY], [minX, maxY], [minX, minY]]],
        annotationListId = this.generateId_(width, height, minX, minY),
        scale = this.image_.width / this.width_,
        coordinatesRescale = [];

    for (var i = 0; i < listCoordinatesWithoutScale[0].length; i++) {
        coordinatesRescale.push([(scale * listCoordinatesWithoutScale[0][i][0]),
            0 - (scale * listCoordinatesWithoutScale[0][i][1])]);
    };
    var listGeometry = new ol.geom.Polygon([coordinatesRescale]),
        listFeature = new ol.Feature(listGeometry);

    listFeature.setId(annotationListId);
    listFeature.setProperties({
        'type': 'annotationList',
        'label': annotationList.label !== null ? annotationList.label : '',
        'width': maxX - minX + 1,
        'height': maxY - minY + 1,
        'x1': minX,
        'y1': minY,
        'x2': maxX,
        'y2': maxY,
        'annotations': annotationFeatures
    });
    listFeature.getAnnotations = function() {
        return annotationFeatures;
    };

    return listFeature;
};

DlfIiifAnnotationParser.prototype.parseGeometry = function(annotation) {
    var xywh = this.getXYWHForAnnotation(annotation),
        coordinatesWithoutScale = [[[xywh.x1, xywh.y1], [xywh.x2, xywh.y1], [xywh.x2, xywh.y2], [xywh.x1, xywh.y2], [xywh.x1, xywh.y1]]];

    if (isNaN(xywh.width) || isNaN(xywh.height))
        return undefined;

    // return geometry without rescale
    if (!dlfUtils.exists(this.image_) || !dlfUtils.exists(this.width_))
        return new ol.geom.Polygon(coordinatesWithoutScale);

    // rescale coordinates
    var scale = this.image_.width / this.width_,
        offset = dlfUtils.exists(this.offset_) ? this.offset_ : 0,
        coordinatesRescale = [];

    for (var i = 0; i < coordinatesWithoutScale[0].length; i++) {
        coordinatesRescale.push([offset + (scale * coordinatesWithoutScale[0][i][0]),
            0 - (scale * coordinatesWithoutScale[0][i][1])]);
    }

    return new ol.geom.Polygon([coordinatesRescale]);
};

/**
 * Get position and dimension from the fragment identifier of the on-uri or from the canvas dimension
 * @param {Object} annotation
 * @return {Object}
 * @private
 */
DlfIiifAnnotationParser.prototype.getXYWHForAnnotation = function (annotation) {
    var fragmentPos = annotation.on.indexOf("#xywh="),
        xywh = fragmentPos > -1 ? annotation.on.substr(fragmentPos+6).split(",") : undefined;
    if (xywh === undefined) return {
        x1: 0,
        y1: 0,
        width: this.width,
        height: this.height,
        x2: this.width - 1,
        y2: this.height - 1
    };
    return {
        x1: parseInt(xywh[0]),
        y1: parseInt(xywh[1]),
        width: parseInt(xywh[2]),
        height: parseInt(xywh[3]),
        x2: parseInt(xywh[0]) + parseInt(xywh[2]) - 1,
        y2: parseInt(xywh[1]) + parseInt(xywh[3]) - 1
    };
};

/**
 * Remove any fragment from the uri
 * @param {string} uri
 * @return {string|null}
 * @private
 */
DlfIiifAnnotationParser.getTargetIdentifierWithoutFragment = function(uri) {
    if (uri === null) {
        return null;
    }
    return uri.split("#")[0];
}
