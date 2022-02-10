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
 * Namespace for dlfViewer OpenLayers sources
 * @namespace
 */
var dlfViewerSource = {};

/**
 * @namespace
 */
dlfViewerSource.IIIF = {};

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
 * @namespace
 */
dlfViewerSource.IIP = {};

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
        resolutions
    };
    return metadataObj;
};
