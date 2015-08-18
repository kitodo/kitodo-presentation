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
 * Zooms in the map. Uses ol.animation for smooth zooming
 */
ol.Map.prototype.zoomIn = function() {
    var view = this.getView(),
        zoomLevel = view.getZoom() + 1,
        resolution = view.getResolution();
    this.beforeRender(ol.animation.zoom({
        resolution: resolution,
        duration: 500
    }));
    view.setZoom(zoomLevel);
};

/**
 * Zooms out the map
 */
ol.Map.prototype.zoomOut = function() {
    var view = this.getView(),
        zoomLevel = view.getZoom() - 1,
     resolution = view.getResolution();
    this.beforeRender(ol.animation.zoom({
        resolution: resolution,
        duration: 500
    }));
    view.setZoom(zoomLevel);
};

/**
 * Zooms to given point
 * @param {Array.<number>} center
 * @param {number} zoomLevel
 */
ol.Map.prototype.zoomTo = function(center, zoomLevel) {
    var view = this.getView(),
        resolution = view.getResolution();
    this.beforeRender(ol.animation.zoom({
        resolution: resolution,
        duration: 500
    }));
    view.setCenter(center);
    view.setZoom(zoomLevel);
};