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

// Internet Explorer does not support String.prototype.endsWith
if (String.prototype.endsWith === undefined) {
    String.prototype.endsWith = function(searchString, length) {
        if (searchString === null || searchString === '' || length !== null && searchString.length > length || searchString.length > this.length) {
            return false;
        }
        length = length === null || length > this.length || length <= 0 ? this.length : length;
        var substr = this.substr(0, length);
        return substr.lastIndexOf(searchString) === length - searchString.length;
    };
}

/**
 * Base namespace for utility functions used by the dlf module.
 *
 * @const
 */
var dlfUtils;
dlfUtils = dlfUtils || {};

/**
 * @type {{ZOOMIFY: string}}
 */
dlfUtils.CUSTOM_MIMETYPE = {
    IIIF: 'application/vnd.kitodo.iiif',
    IIP: 'application/vnd.netfpx',
    ZOOMIFY: 'application/vnd.kitodo.zoomify'
};

/**
 * @type {number}
 */
dlfUtils.RUNNING_INDEX = 99999999;

/**
 * @param imageSourceObjs
 * @param {string} opt_origin
 * @return {Array.<ol.layer.Layer>}
 */
dlfUtils.createOl3Layers = function (imageSourceObjs, opt_origin) {

    var origin = opt_origin !== undefined ? opt_origin : null,
        widthSum = 0,
        offsetWidth = 0,
        layers = [];

    imageSourceObjs.forEach(function (imageSourceObj) {
        var tileSize = void 0;
        if (widthSum > 0) {
            // set offset width in case of multiple images
            offsetWidth = widthSum;
        }

        //
        // Create layer
        //
        var extent = [offsetWidth, -imageSourceObj.height, imageSourceObj.width + offsetWidth, 0],
            layer = void 0;

        if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY) {
            // create zoomify layer
            layer = new ol.layer.Tile({
                source: new ol.source.Zoomify({
                    url: imageSourceObj.src,
                    size: [imageSourceObj.width, imageSourceObj.height],
                    crossOrigin: origin,
                    offset: [offsetWidth, 0]
                }),
                zDirection: -1
            });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIIF) {

            var quality = imageSourceObj.qualities !== undefined && imageSourceObj.qualities.length > 0
                ? $.inArray('color', imageSourceObj.qualities) >= 0
                    ? 'color'
                    : $.inArray('native', imageSourceObj.qualities) >= 0
                        ? 'native'
                        : 'default'
                : 'default';

            layer = new ol.layer.Tile({
                source: new dlfViewerSource.IIIF({
                    url: imageSourceObj.src,
                    version: imageSourceObj.version,
                    size: [imageSourceObj.width, imageSourceObj.height],
                    crossOrigin: origin,
                    resolutions: imageSourceObj.resolutions,
                    tileSize: imageSourceObj.tilesize,
                    sizes: imageSourceObj.sizes,
                    format: 'jpg',
                    quality,
                    supports: imageSourceObj.supports,
                    offset: [offsetWidth, 0],
                    projection: new ol.proj.Projection({
                        code: 'kitodo-image',
                        units: 'pixels',
                        extent: extent
                    })
                }),
                zDirection: -1
            });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIP) {
            tileSize = imageSourceObj.tilesize !== undefined && imageSourceObj.tilesize.length > 0
                ? imageSourceObj.tilesize[0]
                : 256;

            layer = new ol.layer.Tile({
                source: new dlfViewerSource.IIP({
                    url: imageSourceObj.src,
                    size: [imageSourceObj.width, imageSourceObj.height],
                    crossOrigin: origin,
                    tileSize,
                    offset: [offsetWidth, 0]
                }),
                zDirection: -1
            });
        } else {

            // create static image source
            layer = new ol.layer.Image({
                source: new ol.source.ImageStatic({
                    url: imageSourceObj.src,
                    projection: new ol.proj.Projection({
                        code: 'kitodo-image',
                        units: 'pixels',
                        extent: extent
                    }),
                    imageExtent: extent,
                    crossOrigin: origin
                })
            });
        }
        layers.push(layer);

        // add to cumulative width
        widthSum += imageSourceObj.width;
    });

    return layers;
};

/**
 * @param {Array.<{src: *, width: *, height: *}>} images
 * @return {ol.View}
 */
dlfUtils.createOl3View = function (images) {

    //
    // Calculate map extent
    //
    var maxLonX = images.reduce(function (prev, curr) {
        return prev + curr.width;
    }, 0),
        maxLatY = images.reduce(function (prev, curr) {
        return Math.max(prev, curr.height);
    }, 0),
        extent = [0, -maxLatY, maxLonX, 0];

    // globally define max zoom
    window.OL3_MAX_ZOOM = 8;

    // define map projection
    var proj = new ol.proj.Projection({
        code: 'kitodo-image',
        units: 'pixels',
        extent: extent
    });

    // define view
    var viewParams = {
        projection: proj,
        center: ol.extent.getCenter(extent),
        zoom: 1,
        maxZoom: window.OL3_MAX_ZOOM,
        extent
    };

    return new ol.View(viewParams);
};

/**
 * Returns true if the specified value is not undefined
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.exists = function (val) {
    return val !== undefined;
};

/**
 * Fetch image data for given image sources.
 *
 * @param {Array.<{url: *, mimetype: *}>} imageSourceObjs
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.fetchImageData = function (imageSourceObjs) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    /**
     * This holds information about the loading state of the images
     * @type {Array.<number>}
     */
    var imageSourceData = [],
        loadCount = 0,
        finishLoading = function finishLoading() {
        loadCount += 1;

        if (loadCount === imageSourceObjs.length) {
            deferredResponse.resolve(imageSourceData);
        }
    };

    imageSourceObjs.forEach(function (imageSourceObj, index) {
        if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY) {
            dlfUtils.fetchZoomifyData(imageSourceObj)
                .done(function (imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                    finishLoading();
            });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIIF) {
            dlfUtils.getIIIFResource(imageSourceObj)
                .done(function (imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                      finishLoading();
            });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIP) {
            dlfUtils.fetchIIPData(imageSourceObj)
                .done(function (imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                    finishLoading();
            });
        } else {
            // In the worse case expect static image file
            dlfUtils.fetchStaticImageData(imageSourceObj)
                .done(function (imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                    finishLoading();
            });
        }
    });

    return deferredResponse;
};


/**
 * Fetches the image data for static images source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObj
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.fetchStaticImageData = function (imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    // Create new Image object.
    var image = new Image();

    // Register onload handler.
    image.onload = function () {

        var imageDataObj = {
            src: this.src,
            mimetype: imageSourceObj.mimetype,
            width: this.width,
            height: this.height
        };

        deferredResponse.resolve(imageDataObj);
    };

    // Initialize image loading.
    image.src = imageSourceObj.url;

    return deferredResponse;
};

/**
 * @param imageSourceObj
 * @returns {JQueryStatic.Deferred}
 */
dlfUtils.getIIIFResource = function getIIIFResource(imageSourceObj) {

    var deferredResponse = new $.Deferred();
    var type = 'GET';
    $.ajax({
        url: dlfViewerSource.IIIF.getMetdadataURL(imageSourceObj.url),
        type,
        dataType: 'json'
    }).done(cb);

    function cb(data) {
        var mimetype = imageSourceObj.mimetype,
            uri,
            imageResource;
        if (dlfUtils.supportsIIIF(data)) {
            if (data['@context'] && data['@context'] === 'http://iiif.io/api/image/2/context.json') {
                uri = decodeURI(data['@id']);
                uri = dlfUtils.removeInfoJson(uri);
                imageResource = dlfUtils.buildImageV2(mimetype, uri, data);
                deferredResponse.resolve(imageResource);
            } else if (data['@context'] &&
                (data['@context'] === 'http://iiif.io/api/image/3/context.json' ||
                Array.isArray(data['@context']) && data['@context'].includes('http://iiif.io/api/image/3/context.json'))) {
                uri = decodeURI(data['id']);
                uri = dlfUtils.removeInfoJson(uri);
                imageResource = dlfUtils.buildImageV3(mimetype, uri, data);
                deferredResponse.resolve(imageResource);
            } else {
                uri = imageSourceObj.url;
                uri = dlfUtils.removeInfoJson(uri);
                imageResource = dlfUtils.buildImageV1(mimetype, uri, data);
                deferredResponse.resolve(imageResource);
            }
        }
    }

    return deferredResponse;
};

/**
 * @param uri
 * @returns {*}
 */
dlfUtils.removeInfoJson = function removeInfoJson(uri) {
    if (uri.endsWith('/info.json')) {
        uri = uri.substr(0, uri.lastIndexOf('/'));
    }
    return uri;
};

/**
 *
 * @param data
 * @param data.protocol
 * @param data.identifier
 * @param data.width
 * @param data.height
 * @param data.profile
 * @param data.documentElement
 * @returns {boolean}
 */
dlfUtils.supportsIIIF = function supportsIIIF(data) {
    // Version 2.0 and forwards
    if (data.protocol && data.protocol === 'http://iiif.io/api/image' ||
        data['@context'] && data['@context'] === "http://iiif.io/api/image/2/context.json") {
        return true;
        // Version 1.1
    } else if (data['@context'] && (
        data['@context'] === "http://library.stanford.edu/iiif/image-api/1.1/context.json" ||
        data['@context'] === "http://iiif.io/api/image/1/context.json")) {
        return true;
        // Version 1.0
    } else if (data.profile &&
        data.profile.indexOf("http://library.stanford.edu/iiif/image-api/compliance.html") === 0) {
        return true;
    } else if (data.identifier && data.width && data.height) {
        return true;
    } else return data.documentElement && "info" === data.documentElement.tagName &&
    "http://library.stanford.edu/iiif/image-api/ns/" === data.documentElement.namespaceURI;
};

dlfUtils.iiifProfiles = {
    version1: {
        level0: {
            supports: [],
            formats: [],
            qualities: ['native']
        },
        level1: {
            supports: ['regionByPx', 'sizeByW', 'sizeByH', 'sizeByPct'],
            formats: ['jpg'],
            qualities: ['native']
        },
        level2: {
            supports: ['regionByPx', 'regionByPct', 'sizeByW', 'sizeByH', 'sizeByPct',
                'sizeByConfinedWh', 'sizeByWh'],
            formats: ['jpg', 'png'],
            qualities: ['native', 'color', 'grey', 'bitonal']
        }
    },
    version2: {
        level0: {
            supports: [],
            formats: ['jpg'],
            qualities: ['default']
        },
        level1: {
            supports: ['regionByPx', 'sizeByW', 'sizeByH', 'sizeByPct'],
            formats: ['jpg'],
            qualities: ['default']
        },
        level2: {
            supports: ['regionByPx', 'regionByPct', 'sizeByW', 'sizeByH', 'sizeByPct',
                'sizeByConfinedWh', 'sizeByDistortedWh', 'sizeByWh'],
            formats: ['jpg', 'png'],
            qualities: ['default', 'bitonal']
        }
    },
    version3: {
        level0: {
            supports: [],
            formats: ['jpg'],
            qualities: ['default']
        },
        level1: {
            supports: ['regionByPx', 'regionSquare', 'sizeByW', 'sizeByH'],
            formats: ['jpg'],
            qualities: ['default']
        },
        level2: {
            supports: ['regionByPx', 'regionSquare', 'regionByPct',
                'sizeByW', 'sizeByH', 'sizeByPct', 'sizeByConfinedWh', 'sizeByWh'],
            formats: ['jpg'],
            qualities: ['default', 'bitonal']
        }
    },
    none: {
        none: {
            supports: [],
            formats: [],
            qualities: []
        }
    }
};

/**
 *
 * @param mimetype
 * @param uri
 * @param jsonld
 * @param jsonld.width
 * @param jsonld.height
 * @param jsonld.tiles
 * @param jsonld.extraFormats
 * @param jsonld.extraQualities
 * @param jsonld.extraFeatures
 * @param jsonld.profile
 * @returns {{src: *, width, height, tilesize: [*,*], qualities: *, formats: *, resolutions: *, mimetype: *}}
 */
dlfUtils.buildImageV3 = function buildImageV2(mimetype, uri, jsonld) {
    var levelProfile = this.getIiifComplianceLevelProfile(jsonld, 'version3');
    return {
        src: uri,
        version: 'version3',
        width: jsonld.width,
        height: jsonld.height,
        tilesize: jsonld.tiles !== undefined ? [jsonld.tiles.map(function(a) {
            return a.width;
        })[0], jsonld.tiles.map(function (a) {
            return a.height === undefined ? a.width : a.height;
        })[0]] : undefined,
        sizes: jsonld.sizes === undefined ? undefined : jsonld.sizes.map(function(size) {
            return [size.width, size.height];
        }),
        qualities: ['default'].concat(levelProfile.qualities).concat(jsonld.extraQualities === undefined ? [] : jsonld.extraQualities),
        formats: ['jpg'].concat(levelProfile.formats).concat(jsonld.extraFormats === undefined ? [] : jsonld.extraFormats),
        supports: levelProfile.supports.concat(jsonld.extraFeatures === undefined ? [] : jsonld.extraFeatures),
        resolutions: jsonld.tiles !== undefined ? jsonld.tiles.map(function (a) {
            return a.scaleFactors;
        })[0] : [],
        mimetype: mimetype
    };
};

/**
 *
 * @param mimetype
 * @param uri
 * @param jsonld
 * @param jsonld.tiles
 * @param jsonld.width
 * @param jsonld.height
 * @param jsonld.profile
 * @param jsonld.scaleFactors
 * @returns {{src: *, width, height, tilesize: [*,*], qualities: *, formats: *, resolutions: *, mimetype: *}}
 */
dlfUtils.buildImageV2 = function buildImageV2(mimetype, uri, jsonld) {
    if (typeof jsonld.profile === "string") {
        jsonld.profile = [jsonld.profile, {}];
    }
    if (jsonld.profile !== undefined && jsonld.profile.length < 2) {
        jsonld.profile.push({});
    }
    var levelProfile = this.getIiifComplianceLevelProfile(jsonld, 'version2');
    return {
        src: uri,
        version: 'version2',
        width: jsonld.width,
        height: jsonld.height,
        tilesize: jsonld.tiles !== undefined ? [jsonld.tiles.map(function(a) {
            return a.width;
        })[0], jsonld.tiles.map(function (a) {
            return a.height === undefined ? a.width : a.height;
        })[0]] : undefined,
        sizes: jsonld.sizes === undefined ? undefined : jsonld.sizes.map(function(size) {
            return [size.width, size.height];
        }),
        qualities: ['default'].concat(levelProfile.qualities).concat(jsonld.profile[1].qualities === undefined ? [] : jsonld.profile[1].qualities),
        formats: ['jpg'].concat(levelProfile.formats).concat(jsonld.profile[1].formats === undefined ? [] : jsonld.profile[1].formats),
        supports: levelProfile.supports.concat(jsonld.profile[1].supports === undefined ? [] : jsonld.profile[1].supports),
        resolutions: jsonld.tiles !== undefined ? jsonld.tiles.map(function (a) {
            return a.scaleFactors;
        })[0] : [],
        mimetype: mimetype
    };
};

/**
 *
 * @param mimetype
 * @param uri
 * @param jsonld
 * @param jsonld.width
 * @param jsonld.height
 * @param jsonld.scale_factors
 * @param jsonld.tile_width
 * @param jsonld.tile_height
 * @param jsonld.qualities
 * @param jsonld.formats
 * @returns {{src: *, version, width, height, tilesize: [*,*], qualities: *, formats: *, resolutions: *, mimetype: *}}
 */
dlfUtils.buildImageV1 = function buildImageV1(mimetype, uri, jsonld) {
    var levelProfile = this.getIiifComplianceLevelProfile(jsonld, 'version1');
    return {
        src: uri,
        version: 'version1',
        width: jsonld.width,
        height: jsonld.height,
        tilesize: jsonld.tile_width === undefined ? jsonld.tile_height === undefined ? undefined : jsonld.tile_height :
            jsonld.tile_height === undefined ? jsonld.tile_width : [jsonld.tile_width, jsonld.tile_height],
        qualities: ['native'].concat(levelProfile.qualities).concat(jsonld.qualities === undefined ? [] : jsonld.qualities),
        formats: ['jpg'].concat(levelProfile.formats).concat(jsonld.formats === undefined ? [] : jsonld.formats),
        supports:levelProfile.supports,
        resolutions: jsonld.scale_factors,
        mimetype
    };
};

/**
 *
 * @param jsonld
 * @param jsonld.profile
 * @param version
 * @returns string
 */
dlfUtils.getIiifComplianceLevelProfile = function(jsonld, version) {
    var regexVersion1 = new RegExp('^https?\\:\\/\\/library\\.stanford\\.edu\\/iiif\\/image-api\\/(1\\.1\\/)?compliance\\.html#level[0-2]$'),
        regexVersion2 = new RegExp('^https?\\:\\/\\/iiif\\.io\\/api\\/image\\/2\\/level[0-2](\\.json)?$'),
        regexVersion3 = new RegExp('(^https?\\:\\/\\/iiif\\.io\\/api\\/image\\/3\\/level[0-2](\\.json)?$)|(^level[0-2]$)'),
        level;
    if (jsonld.profile === undefined) {
        return dlfUtils.iiifProfiles.none.none;
    }
    switch (version) {
        case 'version1':
            if (regexVersion1.test(jsonld.profile)) {
                level = jsonld.profile;
            }
            break;
        case 'version2':
            if (typeof jsonld.profile === 'string' && regexVersion2.test(jsonld.profile)) {
                level = jsonld.profile;
            }
            if (Array.isArray(jsonld.profile) && jsonld.profile.length >= 1 && typeof jsonld.profile[0] === 'string' && regexVersion2.test(jsonld.profile[0])) {
                level = jsonld.profile[0];
            }
            break;
        case 'version3':
            if (regexVersion3.test(jsonld.profile)) {
                level = jsonld.profile;
            }
            break;
        default:
    }
    if (level !== undefined) {
        level = level.match(/level[0-2](\.json)?$/);
        level = Array.isArray(level) ? level[0].replace('.json', '') : undefined;
        if (level !== undefined) {
            return dlfUtils.iiifProfiles[version][level];
        }
    }
    return dlfUtils.iiifProfiles.none.none;
};

/**
 * Fetches the image data for iip images source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObj
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.fetchIIPData = function (imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    $.ajax({
        url: dlfViewerSource.IIP.getMetdadataURL(imageSourceObj.url) //'http://localhost:8000/fcgi-bin/iipsrv.fcgi?FIF=F4713/HD7.tif&obj=IIP,1.0&obj=Max-size&obj=Tile-size&obj=Resolution-number',
    }).done(cb);
    function cb(response, type) {
        if (type !== 'success') throw new Error('Problems while fetching ImageProperties.xml');

        var imageDataObj = $.extend({
            src: imageSourceObj.url,
            mimetype: imageSourceObj.mimetype
        }, dlfViewerSource.IIP.parseMetadata(response));

        deferredResponse.resolve(imageDataObj);
    }

    return deferredResponse;
};

/**
 * Fetch image data for zoomify source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObj
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.fetchZoomifyData = function (imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    $.ajax({
        url: imageSourceObj.url
    }).done(cb);
    function cb(response, type) {
        if (type !== 'success') {
            throw new Error('Problems while fetching ImageProperties.xml');
        }

        var properties = $(response).find('IMAGE_PROPERTIES');

        var imageDataObj = {
            src: imageSourceObj.url.substring(0, imageSourceObj.url.lastIndexOf("/") + 1),
            width: parseInt(properties.attr('WIDTH')),
            height: parseInt(properties.attr('HEIGHT')),
            tilesize: parseInt(properties.attr('TILESIZE')),
            mimetype: imageSourceObj.mimetype
        };

        deferredResponse.resolve(imageDataObj);
    }

    return deferredResponse;
};

/**
 * @param {string} name Name of the cookie
 * @return {string|null} Value of the cookie
 * @TODO replace unescape function
 */
dlfUtils.getCookie = function (name) {

    var results = document.cookie.match("(^|;) ?" + name + "=([^;]*)(;|$)");

    if (results) {

        return decodeURI(results[2]);
    } else {

        return null;
    }
};

/**
 * Returns url parameters
 * @returns {Object|undefined}
 */
dlfUtils.getUrlParams = function () {
    if (Object.prototype.hasOwnProperty.call(location, 'search')) {
        var search = decodeURIComponent(location.search).slice(1).split('&'),
            params = {};

        search.forEach(function (item) {
            var s = item.split('=');
            params[s[0]] = s[1];
        });

        return params;
    }
    return undefined;
};

/**
 * Returns true if the specified value is null.
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.isNull = function (val) {
    return val === null;
};

/**
 * Returns true if the specified value is null, empty or undefined.
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.isNullEmptyUndefinedOrNoNumber = function (val) {
    return val === null || val === undefined || val === '' || isNaN(val);
};

/**
 * @param {Array.<{url: *, mimetype: *}>} imageObjs
 * @return {boolean}
 */
dlfUtils.isCorsEnabled = function (imageObjs) {
    // fix for proper working with ie
    if (!window.location.origin) {
        window.location.origin = window.location.protocol + '//' + window.location.hostname +
            (window.location.port ? ':' + window.location.port : '');
    }

    // fetch data from server
    // with access control allowed
    var response = true;

    imageObjs.forEach(function (imageObj) {
        var url = imageObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY
            ? imageObj.url.replace('ImageProperties.xml', 'TileGroup0/0-0-0.jpg')
            :
            imageObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIIF
                ? dlfViewerSource.IIIF.getMetdadataURL(imageObj.url)
                : imageObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIP
                ? dlfViewerSource.IIP.getMetdadataURL(imageObj.url)
                : imageObj.url;

        url = window.location.origin + window.location.pathname + '?eID=tx_dlf_pageview_proxy&url=' + encodeURIComponent(url) + '&header=2';

        $.ajax({
            url: url,
            async: false
        }).done(function (data, type) {
            response = type === 'success' && data.indexOf('Access-Control-Allow-Origin') !== -1;
        }).fail(function (data, type) {
            response = false;
        });
    });
    return response;
};

/**
 * Functions checks if WebGL is enabled in the browser
 * @return {boolean}
 */
dlfUtils.isWebGLEnabled = function () {
    if (!window.WebGLRenderingContext) {
        var canvas = document.createElement("canvas"),
            rendererNames = ["webgl", "experimental-webgl", "moz-webgl", "webkit-3d"],
            context = false;

        for (var i = 0; i < rendererNames.length; i++) {
            try {
                context = canvas.getContext(rendererNames[i]);
                if (context && typeof context.getParameter === "function") {
                    // WebGL is enabled;
                    return true;
                }
            } catch (e) {
                /* eslint no-console: ["error", { allow: ["info"] }] */
                console.info(e);
            }
        }
        // WebGL not supported
        return false;
    }

    // WebGL not supported
    return false;
};

/**
 * @param {Element} element
 * @return {Object}
 */
dlfUtils.parseDataDic = function (element) {
    var dataDicString = $(element).attr('data-dic'),
        dataDicRecords = dataDicString.split(';'),
        dataDic = {};

    for (var i = 0, ii = dataDicRecords.length; i < ii; i++) {
        var key = dataDicRecords[i].split(':')[0],
            value = dataDicRecords[i].split(':')[1];
        dataDic[key] = value;
    }

    return dataDic;
};

/**
 * Set a cookie value
 *
 * @param {string} name The key of the value
 * @param {?} value The value to save
 */
dlfUtils.setCookie = function (name, value) {

    document.cookie = name + "=" + decodeURI(value) + "; path=/";
};

/**
 * Scales down the given features geometries. as a further improvement this function
 * adds a unique id to every feature
 * @param {Array.<ol.Feature>} features
 * @param {Object} imageObj
 * @param {number} width
 * @param {number} height
 * @param {number=} opt_offset
 * @deprecated
 * @return {Array.<ol.Feature>}
 */
dlfUtils.scaleToImageSize = function (features, imageObj, width, height, opt_offset) {

    // update size / scale settings of imageObj
    var image = void 0;
    if (width && height) {

        image = {
            'width': width,
            'height': height,
            'scale': imageObj.width / width
        };
    }

    if (image === undefined) return [];

    var scale = image.scale,
        offset = opt_offset !== undefined ? opt_offset : 0;

    // do rescaling and set a id
    for (var i in features) {

        var oldCoordinates = features[i].getGeometry().getCoordinates()[0],
            newCoordinates = [];

        for (var j = 0; j < oldCoordinates.length; j++) {
            newCoordinates.push(
              [offset + scale * oldCoordinates[j][0], 0 - scale * oldCoordinates[j][1]]);
        }

        features[i].setGeometry(new ol.geom.Polygon([newCoordinates]));

        // set index
        dlfUtils.RUNNING_INDEX += 1;
        features[i].setId('' + dlfUtils.RUNNING_INDEX);
    }

    return features;
};

/**
 * Search a feature collcetion for a feature with the given text
 * @param {Array.<ol.Feature>} featureCollection
 * @param {string} text
 * @return {Array.<ol.Feature>|undefined}
 */
dlfUtils.searchFeatureCollectionForText = function (featureCollection, text) {
    var features = [];
    featureCollection.forEach(function (ft) {
        if (ft.get('fulltext') !== undefined) {
            if (ft.get('fulltext').toLowerCase().indexOf(text.toLowerCase()) > -1) features.push(ft);
        }
    });
    return features.length > 0 ? features : undefined;
};
