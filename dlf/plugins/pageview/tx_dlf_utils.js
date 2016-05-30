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
 * Base namespace for utility functions used by the dlf module.
 *
 * @const
 */
var dlfUtils = dlfUtils || {};

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
 * @param {Array.<{src: *, width: *, height: *}>} imageSourceObjs
 * @param {string} mimetype
 * @paran {string} opt_origin
 * @return {Array.<ol.layer.Layer>}
 */
dlfUtils.createOl3Layers = function(imageSourceObjs, opt_origin){

    var origin = opt_origin !== undefined ? opt_origin : null,
        widthSum = 0,
        offsetWidth = 0,
        layers = [];

    imageSourceObjs.forEach(function(imageSourceObj) {
        if (widthSum > 0) {
            // set offset width in case of multiple images
            offsetWidth = widthSum;
        }

        //
        // Create layer
        //
        var extent = [ 0 + offsetWidth, 0, imageSourceObj.width + offsetWidth, imageSourceObj.height],
            layer;

        if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY) {
            // create zoomify layer
            layer = new ol.layer.Tile({
                source: new ol.source.Zoomify({
                    url: imageSourceObj.src,
                    size: [ imageSourceObj.width, imageSourceObj.height ],
                    crossOrigin: origin,
                    offset: [offsetWidth, 0]
                })
            });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIIF) {
            var tileSize = imageSourceObj.tilesize !== undefined && imageSourceObj.tilesize.length > 0
                    ? imageSourceObj.tilesize[0]
                    : 256,
                format = $.inArray('jpg', imageSourceObj.formats) || $.inArray('jpeg', imageSourceObj.formats)
                    ? 'jpg'
                    :  imageSourceObj.formats.length > 0
                        ? imageSourceObj.formats[0]
                        : 'jpg',
                quality = imageSourceObj.qualities !== undefined && imageSourceObj.qualities.length > 0
                    ? imageSourceObj.qualities[0]
                    : 'native';

            layer = new ol.layer.Tile({
                source: new dlfViewerSource.IIIF({
                    url: imageSourceObj.src,
                    size: [ imageSourceObj.width, imageSourceObj.height ],
                    crossOrigin: origin,
                    resolutions: imageSourceObj.resolutions,
                    tileSize: tileSize,
                    format: format,
                    quality: quality,
                    offset: [offsetWidth, 0],
                    projection: new ol.proj.Projection({
                        code: 'goobi-image',
                        units: 'pixels',
                        extent : extent
                    })
                })
            });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIP) {
            var tileSize = imageSourceObj.tilesize !== undefined && imageSourceObj.tilesize.length > 0
                ? imageSourceObj.tilesize[0]
                : 256;

            layer = new ol.layer.Tile({
                source: new dlfViewerSource.IIP({
                    url: imageSourceObj.src,
                    size: [ imageSourceObj.width, imageSourceObj.height ],
                    crossOrigin: origin,
                    tileSize: tileSize,
                    offset: [offsetWidth, 0]
                })
            });
        } else {

            // create static image source
            layer = new ol.layer.Image({
                source: new ol.source.ImageStatic({
                    url: imageSourceObj.src,
                    projection: new ol.proj.Projection({
                        code: 'goobi-image',
                        units: 'pixels',
                        extent : extent
                    }),
                    imageExtent: extent,
                    crossOrigin: origin
                })
            });
        };

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
dlfUtils.createOl3View = function(images) {

    //
    // Calculate map extent
    //
    var maxLonX = images.reduce(function(prev, curr) { return prev + curr.width; }, 0),
        maxLatY = images.reduce(function(prev, curr) { return Math.max(prev, curr.height); }, 0),
        extent = images[0].mimetype !== dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY && images[0].mimetype !== dlfUtils.CUSTOM_MIMETYPE.IIIF &&
                images[0].mimetype !== dlfUtils.CUSTOM_MIMETYPE.IIP
            ? [0, 0, maxLonX, maxLatY]
            : [0, -maxLatY, maxLonX, 0];

    // globally define max zoom
    window.OL3_MAX_ZOOM = 8;

    // define map projection
    var proj = new ol.proj.Projection({
        code: 'goobi-image',
        units: 'pixels',
        extent: extent
    });

    // define view
    var viewParams = {
        projection: proj,
        center: ol.extent.getCenter(extent),
        zoom: 1,
        maxZoom: window.OL3_MAX_ZOOM,
        extent: extent
    };

    return new ol.View(viewParams);
};

/**
 * Returns true if the specified value is not undefiend
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.exists = function(val) {
    return val !== undefined;
};

/**
 * Fetch image data for given image sources.
 *
 * @param {Array.<{url: *, mimetype: *}>} imageSourceObjs
 * @return {jQuery.Deferred.<function(Array.<Object>)>}
 */
dlfUtils.fetchImageData = function(imageSourceObjs) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    /**
     * This holds information about the loading state of the images
     * @type {Array.<number>}
     */
    var imageSourceData = [],
      loadCount = 0,
      finishLoading = function() {
          loadCount += 1;

          if (loadCount === imageSourceObjs.length)
              deferredResponse.resolve(imageSourceData);
      };

    imageSourceObjs.forEach(function(imageSourceObj, index) {
        if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY) {
            dlfUtils.fetchZoomifyData(imageSourceObj)
                .done(function(imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                    finishLoading();
                });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIIF) {
            dlfUtils.fetchIIIFData(imageSourceObj)
              .done(function(imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                    finishLoading();
              });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIP) {
            dlfUtils.fetchIIPData(imageSourceObj)
              .done(function(imageSourceDataObj) {
                  imageSourceData[index] = imageSourceDataObj;
                  finishLoading();
              });
        } else {
            // In the worse case expect static image file
            dlfUtils.fetchStaticImageData(imageSourceObj)
                .done(function(imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                    finishLoading();
                });
        };
    });

    return deferredResponse;
};

/**
 * Fetches the image data for static images source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObj
 * @return {jQuery.Deferred.<function(Array.<Object>)>}
 */
dlfUtils.fetchStaticImageData = function(imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    // Create new Image object.
    var image = new Image();

    // Register onload handler.
    image.onload = function() {

        var imageDataObj = {
            src: this.src,
            mimetype: imageSourceObj.mimetype,
            width: this.width,
            height:  this.height
        };

        deferredResponse.resolve(imageDataObj);
    };

    // Initialize image loading.
    image.src = imageSourceObj.url;

    return deferredResponse;
};

/**
 * Fetches the image data for iiif images source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObj
 * @return {jQuery.Deferred.<function(Array.<Object>)>}
 */
dlfUtils.fetchIIIFData = function(imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    $.ajax({
        url: dlfViewerSource.IIIF.getMetdadataURL(imageSourceObj.url)
    }).done(function(response, type) {
        if (type !== 'success')
            throw new Error('Problems while fetching ImageProperties.xml');

        var imageDataObj = {
            src: imageSourceObj.url,
            width: response.width,
            height: response.height,
            tilesize: [ response.tile_width, response.tile_height ],
            qualities: response.qualities,
            formats: response.formats,
            resolutions: response.scale_factors,
            mimetype: imageSourceObj.mimetype
        };

        deferredResponse.resolve(imageDataObj);
    });

    return deferredResponse;
};

/**
 * Fetches the image data for iip images source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObj
 * @return {jQuery.Deferred.<function(Array.<Object>)>}
 */
dlfUtils.fetchIIPData = function(imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    $.ajax({
        url: dlfViewerSource.IIP.getMetdadataURL(imageSourceObj.url)//'http://localhost:8000/fcgi-bin/iipsrv.fcgi?FIF=F4713/HD7.tif&obj=IIP,1.0&obj=Max-size&obj=Tile-size&obj=Resolution-number',
    }).done(function(response, type) {
        if (type !== 'success')
            throw new Error('Problems while fetching ImageProperties.xml');

        var imageDataObj = $.extend({
            src: imageSourceObj.url,
            mimetype: imageSourceObj.mimetype
        }, dlfViewerSource.IIP.parseMetadata(response));

        deferredResponse.resolve(imageDataObj);
    });

    return deferredResponse;
};

/**
 * Fetch image data for zoomify source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObjs
 * @return {jQuery.Deferred.<function(Array.<Object>)>}
 */
dlfUtils.fetchZoomifyData = function(imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    $.ajax({
        url: imageSourceObj.url
    }).done(function(response, type) {
        if (type !== 'success')
            throw new Error('Problems while fetching ImageProperties.xml');

        var properties = $(response).find('IMAGE_PROPERTIES');

        var imageDataObj = {
            src: response.URL.substring(0, response.URL.lastIndexOf("/") + 1),
            width: parseInt(properties.attr('WIDTH')),
            height: parseInt(properties.attr('HEIGHT')),
            tilesize: parseInt(properties.attr('TILESIZE')),
            mimetype: imageSourceObj.mimetype
        };

        deferredResponse.resolve(imageDataObj);
    });

    return deferredResponse;
};

/**
 * @param {string} name Name of the cookie
 * @return {string|null} Value of the cookie
 * @TODO replace unescape function
 */
dlfUtils.getCookie = function(name) {

    var results = document.cookie.match("(^|;) ?"+name+"=([^;]*)(;|$)");

    if (results) {

        return unescape(results[2]);

    } else {

        return null;

    }

};

/**
 * Returns url parameters
 * @param {string} key
 * @returns {Object|undefined}
 */
dlfUtils.getUrlParams = function() {
    if (location.hasOwnProperty('search')) {
        var search = decodeURIComponent(location.search).slice(1).split('&'),
            params = {};

        search.forEach(function(item) {
           var s = item.split('=');
           params[s[0]] = s[1]
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
dlfUtils.isNull = function(val) {
    return val === null;
};

/**
 * Returns true if the specified value is null, empty or undefined.
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.isNullEmptyOrUndefined = function(val) {
    return val === null || val === undefined || val === '' || val === 'undefined';
};

/**
 * @param {Array.<{url: *, mimetype: *}>} imageObjs
 * @return {boolean}
 */
dlfUtils.isCorsEnabled = function(imageObjs) {
    // fix for proper working with ie
    if (!window.location.origin) {
        window.location.origin = window.location.protocol + '//' + window.location.hostname +
          (window.location.port ? ':' + window.location.port: '');
    }

    // fetch data from server
    // with access control allowed
    var response = true;

    imageObjs.forEach(function(imageObj) {
        var url = imageObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY
          ? imageObj.url.replace('ImageProperties.xml', 'TileGroup0/0-0-0.jpg')
          : imageObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIIF
            ? dlfViewerSource.IIIF.getMetdadataURL(imageObj.url)
            : imageObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIP
                ? dlfViewerSource.IIP.getMetdadataURL(imageObj.url)
                : imageObj.url;

        $.ajax({
            url: url,
            async: false
        }).done(function(data, type) {
            if (type === 'success') {
                response = true && response;
            } else {
                response = false;
            };
        })
        .error(function(data, type) {
            if (type === 'error') {
                response = false;
            }
        });
    });


    return response;
};

/**
 * Functions checks if WebGL is enabled in the browser
 * @return {boolean}
 */
dlfUtils.isWebGLEnabled = function(){
    if (!!window.WebGLRenderingContext) {
       var canvas = document.createElement("canvas"),
           rendererNames = ["webgl", "experimental-webgl", "moz-webgl", "webkit-3d"],
           context = false;

       for (var i = 0; i < rendererNames.length; i++) {
           try {
               context = canvas.getContext(rendererNames[i]);
               if (context && typeof context.getParameter == "function") {
                   // WebGL is enabled;
                   return true;
               }
           } catch(e) {}
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
dlfUtils.parseDataDic = function(element) {
	var dataDicString = $(element).attr('data-dic'),
		dataDicRecords = dataDicString.split(';'),
		dataDic = {};

	for (var i = 0, ii = dataDicRecords.length; i < ii; i++){
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
dlfUtils.setCookie = function(name, value) {

    document.cookie = name+"="+escape(value)+"; path=/";

};

/**
 * Scales down the given features geometrys. as a further improvment this functions
 * add a unique id to every feature
 * @param {Array.<ol.Feature>} features
 * @param {Object} imageObj
 * @param {number} width
 * @param {number} height
 * @param {number=} opt_offset
 * @depreacted
 * @return {Array.<ol.Feature>}
 */
dlfUtils.scaleToImageSize = function(features, imageObj, width, height, opt_offset) {

	// update size / scale settings of imageObj
	var image;
    if (width && height) {

    	image = {
            'width': width,
            'height': height,
            'scale': imageObj.width/width,
        }

    }

    if (image === undefined)
    	return [];

    var scale = image.scale,
    	displayImageHeight = imageObj.height,
    	offset = opt_offset !== undefined ? opt_offset : 0;

    // do rescaling and set a id
    for (var i in features) {

    	var oldCoordinates = features[i].getGeometry().getCoordinates()[0],
    		newCoordinates = [];

    	for (var j = 0; j < oldCoordinates.length; j++) {
    		newCoordinates.push([offset + (scale * oldCoordinates[j][0]), displayImageHeight - (scale * oldCoordinates[j][1])]);
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
dlfUtils.searchFeatureCollectionForText = function(featureCollection, text) {
    var features = [];
    featureCollection.forEach(function(ft) {
        if (ft.get('fulltext') !== undefined) {
            if (ft.get('fulltext').toLowerCase().indexOf(text.toLowerCase()) > -1)
                features.push(ft);
        }
    });
    return features.length > 0 ? features : undefined;
};

/**
 * @param {string} url
 * @param {Function=} opt_callback_ifEnabled Should be called if CORS is enabled
 * @param {Function=} opt_callback_ifNotEnabled Should be called if CORS is not enabled
 * @return {boolean}
 */
dlfUtils.testIfCORSEnabled = function(url, opt_callback_ifEnabled, opt_callback_ifNotEnabled) {
	// fix for proper working with ie
	if (!window.location.origin) {
		window.location.origin = window.location.protocol + '//' + window.location.hostname +
			(window.location.port ? ':' + window.location.port: '');
	}

	// fetch data from server
	// with access control allowed
    var request = $.ajax({
        url: url
    })
    .done(function() {
    	if (opt_callback_ifEnabled !== undefined)
    		opt_callback_ifEnabled();
    })
    .fail(function() {
    	if (opt_callback_ifNotEnabled !== undefined)
    		opt_callback_ifNotEnabled();
    });
};
