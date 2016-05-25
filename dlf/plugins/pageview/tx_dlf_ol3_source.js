/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Kitodo. Key to digital objects e.V. <contact@kitodo.org>
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
 * Namespace for dlfViewer ol3 sources
 * @namespace
 */
var dlfViewerSource = dlfViewerSource || {};

/**
 * OpenLayers 3 compatible source object.
 *
 * Based on the work of Klokan Technologies GmbH (http://www.klokantech.com) and the IIIFViewer. See:
 * https://github.com/klokantech/iiifviewer/blob/master/src/iiifsource.js
 *
 * @param {Object} options
 * @constructor
 */
dlfViewerSource.IIIF = function(options) {

    // parse parameters
    var url = options.url,
        format = options.format !== undefined ? options.format : 'jpg',
        quality = options.quality !== undefined ? options.quality : 'native',
        width = options.size[0],
        height = options.size[1],
        tileSize = options.tileSize !== undefined ? options.tileSize : 256,
        origin = options.crossOrigin !== undefined ? options.crossOrigin : '*',
        offset = options.offset !==  undefined ? options.offset : [0, 0],
        resolutions = $.extend([], options.resolutions),
        projection = options.projection;

    // calculate custom paramters
    var maxZoom = Math.max(
        Math.ceil( Math.log(width / tileSize) / Math.LN2),
        Math.ceil( Math.log(height / tileSize) / Math.LN2)
    );

    var tierSizes = [];
    for (var i = 0; i <= maxZoom; i++) {
        var scale = Math.pow(2, maxZoom - i);
        var width_ = Math.ceil(width / scale);
        var height_ = Math.ceil(height / scale);
        var tilesX_ = Math.ceil(width_ / tileSize);
        var tilesY_ = Math.ceil(height_ / tileSize);
        tierSizes.push([tilesX_, tilesY_]);
    }

    var extent = [offset[0], offset[1] + -height, offset[0] + width, offset[1]];
    var tileGrid = new ol.tilegrid.TileGrid({
            extent: extent,
            resolutions: resolutions.reverse(),
            origin: ol.extent.getTopLeft(extent),
            tileSize: tileSize
        });

    var tileUrlFunction = function(tileCoord, pixelRatio, projection) {

        var z = tileCoord[0];
        if (maxZoom < z) {
            return undefined;
        }

        var sizes = tierSizes[z];
        if (!sizes) {
            return undefined;
        }

        var x = tileCoord[1];
        var y = -tileCoord[2] - 1;
        if (x < 0 || sizes[0] <= x || y < 0 || sizes[1] <= y) {
            return undefined;
        } else {
            var scale = Math.pow(2, maxZoom - z);
            var tileBaseSize = tileSize * scale;
            var minx = x * tileBaseSize;
            var miny = y * tileBaseSize;
            var maxx = Math.min(minx + tileBaseSize, width);
            var maxy = Math.min(miny + tileBaseSize, height);

            maxx = scale * Math.floor(maxx / scale);
            maxy = scale * Math.floor(maxy / scale);

            var query = '/' + minx + ',' + miny + ',' +
              (maxx - minx) + ',' + (maxy - miny) +
              '/pct:' + (100 / scale) + '/0/' + quality + '.' + format;

            return url + query;
        }
    };

    return new ol.source.TileImage({
        crossOrigin: origin,
        projection: projection,
        tileGrid: tileGrid,
        tileUrlFunction: tileUrlFunction
    });
};
