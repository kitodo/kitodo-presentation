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
 * @const
 * @namespace
 */
dlfViewerOLStyles = {};

/**
 * @return {ol.style.Style}
 */
dlfViewerOLStyles.defaultStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(204,204,204,0.8)',
            'width': 3
        }),
        'fill': new ol.style.Fill({
            'color': 'rgba(170,0,0,0.1)'
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerOLStyles.hoverStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(204,204,204,0.8)',
            'width': 1
        }),
        'fill': new ol.style.Fill({
            'color': 'rgba(238,153,0,0.2)'
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerOLStyles.invisibleStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(170,0,0,0)',
            'width': 1
        }),
        'fill': new ol.style.Fill({
            'color': 'transparent'
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerOLStyles.selectStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(170,0,0,0.8)',
            'width': 1
        }),
        'fill': new ol.style.Fill({
            'color': 'rgba(238,153,0,0.2)'
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerOLStyles.textlineStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(170,0,0,1)',
            'width': 1
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerOLStyles.wordStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(238,153,0,0.8)',
            'width': 1
        }),
        'fill': new ol.style.Fill({
            'color': 'rgba(238,153,0,0.2)'
        })
    });

};
