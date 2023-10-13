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
 * @return {number|undefined}
 */
ol.Map.prototype.getZoom = function(){
    return this.getView().getZoom();
};

/**
 * Returns an array containing the min and max zoom level [minZoom, maxZoom]
 * @return {Array.<number>}
 */
ol.Map.prototype.getZoomRange = function() {
    var maxZoom = window.DLF_MAX_ZOOM !== undefined && !isNaN(window.DLF_MAX_ZOOM) ? window.DLF_MAX_ZOOM : 18;
    return [0, maxZoom];
};

/**
 * Zooms to given zoomLevel
 *
 * @param {number} zoomLevel
 */
ol.Map.prototype.zoom = function(zoomLevel) {
    var view = this.getView();
    view.animate({
        'zoom': zoomLevel,
        'duration': 500
    });
};

/**
 * Zooms in the map. Uses view.animate for smooth zooming
 */
ol.Map.prototype.zoomIn = function() {
    var view = this.getView(),
        zoomLevel = view.getZoom() + 1;
    view.animate({
        'zoom': zoomLevel,
        'duration': 500
    });
};

/**
 * Zooms out the map
 */
ol.Map.prototype.zoomOut = function() {
    var view = this.getView(),
        zoomLevel = view.getZoom() - 1;
    view.animate({
        'zoom': zoomLevel,
        'duration': 500
    });
};

/**
 * Zooms to given point
 * @param {Array.<number>} center
 * @param {number} zoomLevel
 * @param {number=} optDuration
 */
ol.Map.prototype.zoomTo = function(center, zoomLevel, optDuration) {
    var view = this.getView(),
        duration = optDuration !== undefined ? optDuration : 500;
    view.animate({
        center,
        'zoom': zoomLevel,
        duration
    });
};

/**
 * Rotate the map
 * @param {number} rotation
 */
ol.Map.prototype.rotate = function(rotation) {
    var view = this.getView(),
        rotate = view.getRotation() + (rotation *  Math.PI/180),
        center = view.getCenter();

    view.animate({
        'rotation': rotate,
        'anchor': center,
        'duration': 200
    });
};

/**
 * Rotate the map in the left direction
 */
ol.Map.prototype.rotateLeft = function() {
    this.rotate(-5);
};

/**
 * Rotate the map in the right direction
 */
ol.Map.prototype.rotateRight = function() {
    this.rotate(5);
};

/**
 * Resets the rotation of the map
 */
ol.Map.prototype.resetRotation = function() {
    this.getView().setRotation(0, this.getView().getCenter());
};
