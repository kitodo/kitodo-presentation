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
    var maxZoom = window.OL3_MAX_ZOOM !== undefined && !isNaN(window.OL3_MAX_ZOOM) ? window.OL3_MAX_ZOOM : 18;
    return [0, maxZoom];
};

/**
 * Zooms to given zoomLevel
 *
 * @param {number} zoomLevel
 */
ol.Map.prototype.zoom = function(zoomLevel) {
    var view = this.getView(),
        resolution = view.getResolution();
    this.beforeRender(ol.animation.zoom({
        'resolution': resolution,
        'duration': 500
    }));
    view.setZoom(zoomLevel);
};

/**
 * Zooms in the map. Uses ol.animation for smooth zooming
 */
ol.Map.prototype.zoomIn = function() {
    var view = this.getView(),
        zoomLevel = view.getZoom() + 1,
        resolution = view.getResolution();
    this.beforeRender(ol.animation.zoom({
        'resolution': resolution,
        'duration': 500
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
        'resolution': resolution,
        'duration': 500
    }));
    view.setZoom(zoomLevel);
};

/**
 * Zooms to given point
 * @param {Array.<number>} center
 * @param {number} zoomLevel
 * @param {number=} opt_duration
 */
ol.Map.prototype.zoomTo = function(center, zoomLevel, opt_duration) {
    var view = this.getView(),
        resolution = view.getResolution(),
        duration = opt_duration !== undefined ? opt_duration : 500;
    this.beforeRender(ol.animation.zoom({
        resolution,
        duration
    }));
    view.setCenter(center);
    view.setZoom(zoomLevel);
};

/**
 * Rotate the map
 * @param {number} rotation
 */
ol.Map.prototype.rotate = function(rotation) {
    var view = this.getView(),
        rotate = view.getRotation() + (rotation *  Math.PI/180),
        center = view.getCenter();

    this.beforeRender(ol.animation.rotate({
        'rotation':view.getRotation(),
        'anchor':center,
        'duration':200
    }));
    view.rotate(rotate, center);
    if (this.ov_view !== null && this.ov_view !== undefined) {
        this.ov_view.rotate(rotate);
    }
};

/**
 * Rotate the map in the left direction
 */
ol.Map.prototype.rotateLeft = function() {
    this.rotate(-5);
    if (this.ov_view !== null && this.ov_view !== undefined) {
        this.ov_view.rotate(-5);
    }
};

/**
 * Rotate the map in the right direction
 */
ol.Map.prototype.rotateRight = function() {
    this.rotate(5);
    if (this.ov_view !== null && this.ov_view !== undefined) {
        this.ov_view.rotate(5);
    }
};

/**
 * Resets the rotation of the map
 */
ol.Map.prototype.resetRotation = function() {
    this.getView().rotate(0, this.getView().getCenter());
    if (this.ov_view !== null && this.ov_view !== undefined) {
        this.ov_view.rotate(0);
    }
};
