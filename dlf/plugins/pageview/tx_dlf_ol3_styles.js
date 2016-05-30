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
 * @const
 * @namespace
 */
dlfViewerOL3Styles = {};

/**
 * @return {ol.style.Style}
 */
dlfViewerOL3Styles.defaultStyle = function() {

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
dlfViewerOL3Styles.hoverStyle = function() {

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
dlfViewerOL3Styles.invisibleStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(170,0,0,0)',
            'width': 1
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerOL3Styles.selectStyle = function() {

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
dlfViewerOL3Styles.textlineStyle = function() {

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
dlfViewerOL3Styles.wordStyle = function() {

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