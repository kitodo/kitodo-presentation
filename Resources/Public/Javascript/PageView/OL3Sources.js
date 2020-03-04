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
 * Polyfill for Number.isInteger
 */
Number.isInteger = Number.isInteger || function(value) {
    return typeof value === 'number' && isFinite(value) && Math.floor(value) === value;
};

/**
 * Polyfill for Number.isNaN
 */
Number.isNaN = Number.isNaN || function(value) {
    return value !== null && (value !== value || +value !== value);
};

/**
 * Polyfill for Array.prototype.includes
 */
Array.prototype.includes = Array.prototype.includes || function(value) {
    return this.indexOf(value) > -1;
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
    var img = tile.getImage(),
        tileWidth = Array.isArray(tileSize) ? tileSize[0] : tileSize,
        tileHeight = Array.isArray(tileSize) ? tileSize[1] : tileSize;
    $(img).load(function() {
        if (img.naturalWidth > 0 &&
          (img.naturalWidth !== tileWidth || img.naturalHeight !== tileHeight)) {
            var canvas = document.createElement('canvas');
            canvas.width = tileWidth;
            canvas.height = tileHeight;

            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            var key = dlfViewerSource.findKey(tile, function(v) {return v === img;});
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
    var url = options.url.replace(/info.json$/, ''),
        version = options.version || 'version2',
        format = options.format !== undefined ? options.format : 'jpg',
        quality = options.quality !== undefined ? options.quality : options.version === 'version1' ? 'native' : 'default',
        width = options.size[0],
        height = options.size[1],
        tileSize = options.tileSize,
        origin = options.crossOrigin !== undefined ? options.crossOrigin : '*',
        offset = options.offset !==  undefined ? options.offset : [0, 0],
        resolutions = $.extend([], options.resolutions),
        sizes = options.sizes === undefined ? [] : options.sizes,
        supports = options.supports === undefined ? [] : options.supports,
        supportsListedSizes = sizes !== undefined && Array.isArray(sizes) && sizes.length > 0,
        supportsListedTiles = tileSize !== undefined && (Number.isInteger(tileSize) && tileSize > 0 || Array.isArray(tileSize) && tileSize.length > 0),
        supportsArbitraryTiling = supports !== undefined && Array.isArray(supports) &&
            (supports.includes('regionByPx') || supports.includes('regionByPct')) &&
            (supports.includes('sizeByWh') || supports.includes('sizeByH') ||
            supports.includes('sizeByW') || supports.includes('sizeByPct')),
        projection = options.projection,
        tileWidth,
        tileHeight,
        maxZoom;

    url += url.lastIndexOf('/') === url.length - 1 ? '' : '/';
    // sort resolutions because the spec does not specify any order
    resolutions.sort(function(a, b) {
        return b - a;
    });

    if (supportsListedTiles || supportsArbitraryTiling) {
        if (tileSize !== undefined) {
            if (Number.isInteger(tileSize) && tileSize > 0) {
              tileWidth = tileSize;
              tileHeight = tileSize;
            } else if (Array.isArray(tileSize) && tileSize.length > 0) {
                if (tileSize.length === 1 || tileSize[1] === undefined && Number.isInteger(tileSize[0])) {
                    tileWidth = tileSize[0];
                    tileHeight = tileSize[0];
                }
                if (tileSize.length === 2) {
                    if (Number.isInteger(tileSize[0]) && Number.isInteger(tileSize[1])) {
                        tileWidth = tileSize[0];
                        tileHeight = tileSize[1];
                    } else if (tileSize[0] === undefined && Number.isInteger(tileSize[1])) {
                        tileWidth = tileSize[1];
                        tileHeight = tileSize[1];
                    }
                }
            }
        }
        if (tileWidth === undefined || tileHeight === undefined) {
            tileWidth = 256;
            tileHeight = 256;
        }
        if (resolutions.length === 0) {
            maxZoom = Math.max(
                Math.ceil(Math.log(width / tileWidth) / Math.LN2),
                Math.ceil(Math.log(height / tileHeight) / Math.LN2)
            );
            for (var i = maxZoom; i >= 0; i--) {
                resolutions.push(Math.pow(2, i));
           }
         } else {
            var maxScaleFactor = Math.max.apply(null, resolutions);
            // TODO maxScaleFactor might not be a power to 2
            maxZoom = Math.round(Math.log(maxScaleFactor) / Math.LN2);
        }
    } else {
        // No tile support.
        tileWidth = width;
        tileHeight = height;
        resolutions = [];
        if (supportsListedSizes) {
            /*
             * 'sizes' provided. Use full region in different resolutions. Every
             * resolution has only one tile.
             */
            sizes.sort(function(a, b) {
                return a[0] - b[0];
            });
            maxZoom = -1;
            var ignoredSizesIndex = [];
            for (var i = 0; i < sizes.length; i++) {
                var resolution = width / sizes[i][0];
                if (resolutions.length > 0 && resolutions[resolutions.length - 1] === resolution) {
                    ignoredSizesIndex.push(i);
                    continue;
                }
                resolutions.push(resolution);
                maxZoom++;
            }
            if (ignoredSizesIndex.length > 0) {
                for (var j = 0; j < ignoredSizesIndex.length; j++) {
                    sizes.splice(ignoredSizesIndex[j] - j, 1);
                }
            }
        } else {
            // No useful image information at all. Try pseudo tile with full image.
            resolutions.push(1);
            sizes.push([width, height]);
            maxZoom = 0;
        }
    }

    // define tilegrid with offset extent
    var extent = [offset[0], offset[1] - height, offset[0] + width, offset[1]];
    var tileGrid = new ol.tilegrid.TileGrid({
        extent,
        resolutions,
        origin: ol.extent.getTopLeft(extent),
        tileSize: [tileWidth, tileHeight]
    });

    /**
     * @this {ol.source.TileImage}
     * @param {ol.TileCoord} tileCoord Tile Coordinate.
     * @param {number} pixelRatio Pixel ratio.
     * @param {ol.proj.Projection} projection Projection.
     * @return {string|undefined} Tile URL.
     */
    var tileUrlFunction = function(tileCoord, pixelRatio, projection) {
        var regionParam,
            sizeParam,
            zoom = tileCoord[0];
        if (zoom > maxZoom) {
            return;
        }
        var tileX = tileCoord[1],
            tileY = -tileCoord[2] - 1,
            scale = resolutions[zoom];
        if (tileX === undefined || Number.isNaN(tileY) || scale === undefined ||
                tileX < 0 || Math.ceil(width / scale / tileWidth) <= tileX ||
                tileY < 0 || Math.ceil(height / scale / tileHeight) <= tileY) {
            return;
        }
        if (supportsArbitraryTiling || supportsListedTiles) {
            var regionX = tileX * tileWidth * scale,
                regionY = tileY * tileHeight * scale;
            var regionW = tileWidth * scale,
                regionH = tileHeight * scale,
                sizeW = tileWidth,
                sizeH = tileHeight;
            if (regionX + regionW > width) {
                regionW = width - regionX;
            }
            if (regionY + regionH > height) {
                regionH = height - regionY;
            }
            if (regionX + tileWidth * scale > width) {
                sizeW = Math.floor((width - regionX + scale - 1) / scale);
            }
            if (regionY + tileHeight * scale > height) {
                sizeH = Math.floor((height - regionY + scale - 1) / scale);
            }
            if (regionX === 0 && regionW === width && regionY === 0 && regionH === height) {
                // canonical full image region parameter is 'full', not 'x,y,w,h'
                regionParam = 'full';
            } else if (!supportsArbitraryTiling || supports.includes('regionByPx')) {
                regionParam = regionX + ',' + regionY + ',' + regionW + ',' + regionH;
            } else if (supports.includes('regionByPct')) {
                var pctX = formatPercentage(regionX / width * 100),
                    pctY = formatPercentage(regionY / height * 100),
                    pctW = formatPercentage(regionW / width * 100),
                    pctH = formatPercentage(regionH / height * 100);
                regionParam = 'pct:' + pctX + ',' + pctY + ',' + pctW + ',' + pctH;
            }
            if (version === 'version3' && (!supportsArbitraryTiling || supports.includes('sizeByWh'))) {
                sizeParam = sizeW + ',' + sizeH;
            } else if (!supportsArbitraryTiling || supports.includes('sizeByW')) {
                sizeParam = sizeW + ',';
            } else if (supports.includes('sizeByH')) {
                sizeParam = ',' + sizeH;
            } else if (supports.includes('sizeByWh')) {
                sizeParam = sizeW + ',' + sizeH;
            } else if (supports.includes('sizeByPct')) {
                sizeParam = 'pct:' + formatPercentage(100 / scale);
            }
        } else {
            regionParam = 'full';
            if (supportsListedSizes) {
                var regionWidth = sizes[zoom][0],
                    regionHeight = sizes[zoom][1];
                if (version === 'version3') {
                    if (regionWidth === width && regionHeight === height) {
                        sizeParam = 'max';
                    } else {
                        sizeParam = regionWidth + ',' + regionHeight;
                    }
                } else {
                    if (regionWidth === width) {
                        sizeParam = 'full';
                    } else {
                        sizeParam = regionWidth + ',';
                    }
                }
            } else {
                sizeParam = version === 'version3' ? 'max' : 'full';
            }
        }
        return url + regionParam + '/' + sizeParam + '/0/' + quality + '.' + format;
    };

    var tileImageParams = {
        crossOrigin: origin,
        projection,
        tileGrid,
        tileUrlFunction
    };

    if (ol.has.CANVAS) {
        tileImageParams.tileLoadFunction = dlfViewerSource.tileLoadFunction.bind(this, [tileWidth, tileHeight]);
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

    }
    resolutions.push( res );
    tierSizeInTiles.push( [1,1]);
    tierSizeInTiles.reverse();

    var extent = [offset[0], offset[1] - height, offset[0] + width, offset[1]];
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
