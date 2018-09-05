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
 * Namespace for dlfViewer ol3 sources
 * @namespace
 */
var dlfViewerSource = dlfViewerSource || {};

/**
 * Utility Function based on Google Closure Library.
 * http://google.github.io/closure-library/api/source/closure/goog/object/object.js.src.html#l306
 *
 * Searches an object for an element that satisfies the given condition and
 * returns its key.
 *
 * @param {Object<K,V>} obj The object to search in.
 * @param {function(this:T,V,string,Object<K,V>):boolean} f The
 *      function to call for every element. Takes 3 arguments (the value,
 *     the key and the object) and should return a boolean.
 * @param {T=} opt_this An optional "this" context for the function.
 * @return {string|undefined} The key of an element for which the function
 *     returns true or undefined if no such element is found.
 */
dlfViewerSource.findKey = function(obj, f, opt_this) {
    for (var key in obj) {
        if (f.call(/** @type {?} */ (opt_this), obj[key], key, obj)) {
            return key;
        }
    }
    return undefined;
};

/**
 * OpenLayers 3 TileLoadFunction based on the work of Klokan Technologies GmbH (http://www.klokantech.com) and
 * the IIIFViewer. See: https://github.com/klokantech/iiifviewer/blob/master/src/iiifsource.js
 *
 * @param {number} tileSize
 * @param {ol.ImageTile} tile
 * @param {string} url
 */
dlfViewerSource.tileLoadFunction = function(tileSize, tile, url) {
    var img = tile.getImage();
    $(img).load(function() {
        if (img.naturalWidth > 0 &&
          (img.naturalWidth != tileSize || img.naturalHeight != tileSize)) {
            var canvas = document.createElement('canvas');
            canvas.width = tileSize;
            canvas.height = tileSize;

            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            var key = dlfViewerSource.findKey(tile, function(v) {return v == img;});
            if (key) {
                tile[key] = canvas;
            }
        }
    });
    img.src = url;
};

/**
 * OpenLayers 3 compatible source object for an iiif server.
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

    // define tilegrid with offset extent
    var extent = [offset[0], offset[1] + -height, offset[0] + width, offset[1]];
    var tileGrid = new ol.tilegrid.TileGrid({
            extent: extent,
            resolutions: resolutions.reverse(),
            origin: ol.extent.getTopLeft(extent),
            tileSize: tileSize
        });

    /**
     * @this {ol.source.TileImage}
     * @param {ol.TileCoord} tileCoord Tile Coordinate.
     * @param {number} pixelRatio Pixel ratio.
     * @param {ol.proj.Projection} projection Projection.
     * @return {string|undefined} Tile URL.
     */
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

    var tileImageParams = {
        crossOrigin: origin,
        projection: projection,
        tileGrid: tileGrid,
        tileUrlFunction: tileUrlFunction
    };

    if (ol.has.CANVAS) {
        tileImageParams.tileLoadFunction = dlfViewerSource.tileLoadFunction.bind(this, tileSize);
    }

    return new ol.source.TileImage(tileImageParams);
};

/**
 * Returns an iiif compatible metadata url.
 *
 * @param {string} baseUrl
 * @return {string}
 */
dlfViewerSource.IIIF.getMetdadataURL = function(baseUrl) {
    var pathString = baseUrl.lastIndexOf('/') + 1 === baseUrl.length
        ? 'info.json'
        : '/info.json';

    return baseUrl + pathString;
};

/**
 * OpenLayers 3 compatible source object for an iip server.
 *
 *
 * @param {Object} options
 * @constructor
 */
dlfViewerSource.IIP = function(options) {

    // parse parameters
    var url = options.url.indexOf('?') > -1
          ? options.url
          : options.url + '?',
        width = options.size[0],
        height = options.size[1],
        tileSize = options.tileSize !== undefined ? options.tileSize : 256,
        origin = options.crossOrigin !== undefined ? options.crossOrigin : '*',
        offset = options.offset !==  undefined ? options.offset : [0, 0],
        resolutions = [],
        projection = options.projection;

    // calculate tiersize in tiles and resolutions
    var tierSizeInTiles = [],
        imageWidth = width,
        imageHeight = height,
        res = 1;
    while (imageWidth > tileSize || imageHeight > tileSize) {

        tierSizeInTiles.push([
            Math.ceil(imageWidth / tileSize),
            Math.ceil(imageHeight / tileSize)
        ]);
        resolutions.push( res );

        imageWidth >>= 1;
        imageHeight >>= 1;
        res += res;

    };
    resolutions.push( res );
    tierSizeInTiles.push( [1,1]);
    tierSizeInTiles.reverse();

    var extent = [offset[0], offset[1] + -height, offset[0] + width, offset[1]];
    var tileGrid = new ol.tilegrid.TileGrid({
        extent: extent,
        resolutions: resolutions.reverse(),
        origin: ol.extent.getTopLeft(extent),
        tileSize: tileSize
    });

    /**
     * @this {ol.source.TileImage}
     * @param {ol.TileCoord} tileCoord Tile Coordinate.
     * @param {number} pixelRatio Pixel ratio.
     * @param {ol.proj.Projection} projection Projection.
     * @return {string|undefined} Tile URL.
     */
    var tileUrlFunction = function(tileCoord, pixelRatio, projection) {
        if (tileCoord === undefined ||tileCoord === null) {
            return undefined;
        } else {
            var resolution = tileCoord[0],
                tileCoordX = tileCoord[1],
                tileCoordY = -tileCoord[2] - 1,
                tileIndex = tileCoordY * tierSizeInTiles[resolution][0] + tileCoordX;
            return url + '&JTL=' + resolution + ',' + tileIndex;
        }
    };

    var tileImageParams = {
        crossOrigin: origin,
        projection: projection,
        tileGrid: tileGrid,
        tileUrlFunction: tileUrlFunction
    };

    if (ol.has.CANVAS) {
        tileImageParams.tileLoadFunction = dlfViewerSource.tileLoadFunction.bind(this, tileSize);
    }

    return new ol.source.TileImage(tileImageParams);


};

/**
 * Returns an iip compatible metadata url.
 *
 * @param {string} baseUrl
 * @return {string}
 */
dlfViewerSource.IIP.getMetdadataURL = function(baseUrl) {
    var queryString = '&obj=IIP,1.0&obj=Max-size&obj=Tile-size&obj=Resolution-number',
        url = baseUrl.indexOf('?') > -1
          ? baseUrl
          : baseUrl + '?';
    return url + queryString;
};

/**
 * Function parses a given string response for a iip metadata request.
 *
 * @param {string} metadataString
 * @return {Object}
 */
dlfViewerSource.IIP.parseMetadata = function(metadataString) {

    // parse size
    var maxSize = metadataString.split( "Max-size" );
    if(!maxSize[1]) {
        return null;
    }
    var size = maxSize[1].split(" ");

    // parse tile size
    var tileSizeTmp = metadataString.split( "Tile-size" );
    if(!tileSizeTmp[1]) {
        return null;
    }
    var tileSize = tileSizeTmp[1].split(" ");

    // parse resolution
    var resolutionNumTmp = metadataString.split( "Resolution-number"),
        resolutionNum = parseInt( resolutionNumTmp[1].substring(1,resolutionNumTmp[1].length)),
        res = 1,
        resolutions = [];
    for (var i = 0; i <resolutionNum; i++) {
        resolutions.push(res);
        res += res;
    }

    var metadataObj = {
        'width': parseInt(size[0].substring(1,size[0].length)),
        'height': parseInt(size[1]),
        'tilesize': [parseInt(tileSize[0].substring(1,tileSize[0].length)), parseInt(tileSize[1]) ],
        'resolutions': resolutions
    };
    return metadataObj;
};
