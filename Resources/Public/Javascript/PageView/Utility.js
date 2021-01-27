/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

'use strict';

/**
 * Namespace for dlfViewer utility functions.
 * @namespace
 */
var dlfUtils;
dlfUtils = dlfUtils || {};

/**
 * Custom MIMETYPEs for image server protocols.
 * @type {{IIIF: *, IIP: *, ZOOMIFY: *}}
 */
dlfUtils.CUSTOM_MIMETYPE = {
    IIIF: 'application/vnd.kitodo.iiif',
    IIP: 'application/vnd.netfpx',
    ZOOMIFY: 'application/vnd.kitodo.zoomify'
};

/**
 * Returns true if the specified variable is set and not empty
 * @param {?} variable
 * @return {boolean}
 */
dlfUtils.exists = function(variable) {
    return Boolean(variable) && variable !== "0";
};

/**
 * Get image metadata for IIIF image source
 *
 * @param {url: *, mimetype: *} imageSource
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.getIIIFMetadata = function (imageSource) {
    var deferredResponse = new $.Deferred();
    var metadata = {};
    var tileSourceOptions = undefined;
    fetch(imageSource.url + (imageSource.url.endsWith('/') ? 'info.json' : '/info.json'))
        .then((response) => response.json())
        .then((imageInfo) => {
            tileSourceOptions = new ol.format.IIIFInfo(imageInfo)
                .getTileSourceOptions({
                    format: 'jpg',
                    quality: 'color'
                });
            metadata = {
                width: tileSourceOptions.size[0],
                height: tileSourceOptions.size[1],
                options: tileSourceOptions
            };
            deferredResponse.resolve(metadata);
        });
    return deferredResponse;
};

/**
 * Get image metadata for IIP image source
 *
 * @param {url: *, mimetype: *} imageSource
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.getIIPMetadata = function (imageSource) {
    var deferredResponse = new $.Deferred();
    var metadata = {};
    var tileSourceOptions = undefined;
    fetch(imageSource.url + (imageSource.url.includes('?') ? '&' : '?') + 'obj=IIP,1.0&obj=Max-size&obj=Tile-size')
        .then((response) => response.text())
        .then((imageInfo) => {
            var size = [0, 0];
            var tileSize = 256;
            // imageInfo is a plain-text multi-line string:
            // IIP:1.0
            // Max-size:WIDTH HEIGHT
            // Tile-size:TILEWIDTH TILEHEIGHT
            imageInfo.replace(/\r\n/g, '\n')
                .split('\n')
                .forEach((textLine) => {
                    if (textLine.startsWith('Max-size')) {
                        size = textLine.match(/[0-9]+/g).map((x) => parseInt(x));
                    } else if (textLine.startsWith('Tile-size')) {
                        tileSize = parseInt(textLine.match(/[0-9]+/)[0]);
                    }
                });
            tileSourceOptions = {
                url: imageSource.url + (imageSource.url.includes('?') ? '&' : '?') + 'JTL={z},{tileIndex}',
                size,
                tileSize
            };
            metadata = {
                width: size[0],
                height: size[1],
                options: tileSourceOptions
            };
            deferredResponse.resolve(metadata);
        });
    return deferredResponse;
};

/**
 * Get image metadata for given image sources
 *
 * @param {Array.<{url: *, mimetype: *}>} imageSources
 * @param {Object} context
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.getImageMetadata = function (imageSources, context) {
    var deferredResponse = new $.Deferred();
    var deferredMetadata = undefined;
    var imageMetadata = [];
    var loadCount = 0;
    var checkResolve = function checkResolve() {
        loadCount += 1;
        if (loadCount === imageSources.length) {
            deferredResponse.resolveWith(context, [imageMetadata]);
        }
    };
    imageSources.forEach((source, index) => {
        switch (source.mimetype) {
            case dlfUtils.CUSTOM_MIMETYPE.IIIF:
                deferredMetadata = dlfUtils.getIIIFMetadata(source);
                break;
            case dlfUtils.CUSTOM_MIMETYPE.IIP:
                deferredMetadata = dlfUtils.getIIPMetadata(source);
                break;
            case dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY:
                deferredMetadata = dlfUtils.getZoomifyMetadata(source);
                break;
            default:
                deferredMetadata = dlfUtils.getStaticMetadata(source);
                break;
        }
        deferredMetadata.done(function (sourceMetadata) {
            imageMetadata[index] = sourceMetadata;
            checkResolve();
        });
    });
    return deferredResponse;
};

/**
 * Get image metadata for static image source
 *
 * @param {url: *, mimetype: *} imageSource
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.getStaticMetadata = function (imageSource) {
    var deferredResponse = new $.Deferred();
    var metadata = {};
    var tileSourceOptions = undefined;
    var image = new Image();
    image.onload = function() {
        tileSourceOptions = {
            imageSize: [this.width, this.height],
            url: this.src
        };
        metadata = {
            width: this.width,
            height: this.height,
            options: tileSourceOptions
        };
        deferredResponse.resolve(metadata);
    };
    image.src = imageSource.url;
    return deferredResponse;
};

/**
 * Get image metadata for Zoomify image source
 *
 * @param {url: *, mimetype: *} imageSource
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.getZoomifyMetadata = function (imageSource) {
    var deferredResponse = new $.Deferred();
    var metadata = {};
    var tileSourceOptions = undefined;
    fetch(imageSource.url + (imageSource.url.endsWith('/') ? 'ImageProperties.xml' : '/ImageProperties.xml'))
        .then((response) => response.text())
        .then((imageInfo) => {
            // imageInfo is a XML string:
            // <IMAGE_PROPERTIES WIDTH="{WIDTH}" HEIGHT="{HEIGHT}" TILESIZE="{TILESIZE}" />
            var imageProperties = $($.parseXML(imageInfo)).find('IMAGE_PROPERTIES');
            var width = parseInt(imageProperties.attr('WIDTH'));
            var height = parseInt(imageProperties.attr('HEIGHT'));
            var tileSize = parseInt(imageProperties.attr('TILESIZE')) || 256;
            tileSourceOptions = {
                url: imageSource.url + (imageSource.url.endsWith('/') ? '' : '/'),
                size: [width, height],
                tileSize
            };
            metadata = {
                width,
                height,
                options: tileSourceOptions
            };
            deferredResponse.resolve(metadata);
        });
    return deferredResponse;
};

/**
 * Check if image sources have CORS enabled
 * @param {Array.<{url: *, mimetype: *}>} images
 * @param {Object} context
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.isCorsEnabled = function (images, context) {
    var deferredResponse = new $.Deferred();
    var corsEnabled = false;
    var loadCount = 0;
    var checkResolve = function checkResolve() {
        loadCount += 1;
        if (loadCount === images.length) {
            deferredResponse.resolveWith(context, [corsEnabled]);
        }
    };
    // Use image proxy to get CORS headers.
    images.forEach((image) => {
        var url = image.url;
        switch (image.mimetype) {
            case dlfUtils.CUSTOM_MIMETYPE.IIIF:
                url += url.endsWith('/') ? 'info.json' : '/info.json';
                break;
            case dlfUtils.CUSTOM_MIMETYPE.IIP:
                url += url.includes('?') ? '&obj=IIP,1.0' : '?obj=IIP,1.0';
                break;
            case dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY:
                url += url.endsWith('/') ? 'ImageProperties.xml' : '/ImageProperties.xml';
                break;
        }
        // Prepend image proxy.
        url = window.location.origin + window.location.pathname + '?eID=tx_dlf_pageview_proxy&url=' + encodeURIComponent(url) + '&header=2';
        // Get header data.
        $.ajax({url})
            .done((data, type) => {
                corsEnabled = type === 'success' && data.indexOf('Access-Control-Allow-Origin') !== -1;
                checkResolve();
            });
    });
    return deferredResponse;
};
