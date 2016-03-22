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
 * Base namespace for utility functions used by the dlf module.
 *
 * @const
 */
var dlfUtils = dlfUtils || {};

/**
 * @type {number}
 */
dlfUtils.RUNNING_INDEX = 99999999;

/**
 * @param {Array.<{src: *, width: *, height: *}>} images
 * @return {Array.<ol.layer.Layer>}
 */
dlfUtils.createLayers = function(images, opt_renderer){

    // create image layers
    var layers = [],
    	renderer = opt_renderer !== undefined ? opt_renderer : 'webgl';
    	crossOrigin = renderer == 'webgl' ? '*' : null;

    for (var i = 0; i < images.length; i++) {

        var layerExtent = i === 0 ? [0 , 0, images[i].width, images[i].height] :
            [images[i-1].width , 0, images[i].width + images[i-1].width, images[i].height];

        var layerProj = new ol.proj.Projection({
                code: 'goobi-image',
                units: 'pixels',
                extent: layerExtent
            }),
            layer = new ol.layer.Image({
                source: new ol.source.ImageStatic({
                    url: images[i].src,
                    projection: layerProj,
                    imageExtent: layerExtent,
                    crossOrigin: crossOrigin
                })
            });
        layers.push(layer);
    }

    return layers;
};

/**
 * @param {Array.<{src: *, width: *, height: *}>} images
 * @return {ol.View}
 */
dlfUtils.createView = function(images) {
    // create map extent
    var maxx = images.length === 1 ? images[0].width : images[0].width + images[1].width,
        maxy = images.length === 1 ? images[0].height : Math.max(images[0].height, images[1].height),
        mapExtent = [0, 0, maxx, maxy],
        mapProj = new ol.proj.Projection({
            code: 'goobi-image',
            units: 'pixels',
            extent: mapExtent
        }),
        mapView = new ol.View({
            projection: mapProj,
            center: ol.extent.getCenter(mapExtent),
            zoom: 0,
            maxZoom: 8,
            extent: mapExtent
        });
    return mapView;
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
 * Returns true if the specified value is null.
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.isNull = function(val) {
    return val === null;
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
 * @return {ol.Feature|undefined}
 */
dlfUtils.searchFeatureCollectionForText = function(featureCollection, text) {
    var feature;
    featureCollection.forEach(function(ft) {
        if (ft.get('fulltext') !== undefined) {
            if (ft.get('fulltext') === text)
                feature = ft;
        }
    });
    return feature;
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
