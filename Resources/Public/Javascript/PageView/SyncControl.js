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
 * Encapsulates especially the score behavior
 * @constructor
 * @param {ol.Map} map
 */
const dlfViewerSyncControl = function(dlfViewer, sync = true) {
    this.dlfViewer = dlfViewer;
    this.sync = sync;
    this.dx = 0;
    this.dy = 0;
    this.dz = 0;
    this.dr = 0;
};

dlfViewerSyncControl.prototype.addSyncControl = function () {
    this.dlfViewer.map.addControl(new SyncViewsControl());
    var controlContext = this;
    controlContext.addMapEventListener();
}

dlfViewerSyncControl.prototype.addMapEventListener = function () {
    var controlContext = this;
    this.dlfViewer.scoreMap.getView().on(['change:center','change:resolution','change:rotation'], function() {
        if (controlContext.sync) {
            var map1 = tx_dlf_viewer.map;
            var map2 = tx_dlf_viewer.scoreMap;
            var center = map2.getView().getCenter();
            var zoom = map2.getView().getZoom();
            var rotation = map2.getView().getRotation();
            controlContext.sync = false;
            map1.getView().animate({
                center: [center[0] - controlContext.dx, center[1] - controlContext.dy],
                zoom: zoom - controlContext.dz,
                rotation: rotation - controlContext.dr,
                duration: 0
            }, function() { controlContext.sync = true; });
        }
    });

    this.dlfViewer.map.getView().on(['change:center','change:resolution','change:rotation'], function() {
        if (controlContext.sync) {
            var map1 = tx_dlf_viewer.scoreMap;
            var map2 = tx_dlf_viewer.map;
            var center = map2.getView().getCenter();
            var zoom = map2.getView().getZoom();
            var rotation = map2.getView().getRotation();
            controlContext.sync = false;
            if (map1) {
                map1.getView().animate({
                    center: [center[0] - controlContext.dx, center[1] - controlContext.dy],
                    zoom: zoom - controlContext.dz,
                    rotation: rotation - controlContext.dr,
                    duration: 0
                }, function () {
                    controlContext.sync = true;
                });
            }
        }
    });
}

dlfViewerSyncControl.prototype.setSync = function () {
    this.sync = true;
    var map1 = tx_dlf_viewer.scoreMap;
    var map2 = tx_dlf_viewer.map;
    var center1 = map1.getView().getCenter();
    var center2 = map2.getView().getCenter();
    this.dx = center2[0] - center1[0];
    this.dy = center2[1] - center1[1];
    var zoom1 = map1.getView().getZoom();
    var zoom2 = map2.getView().getZoom();
    this.dz = zoom2 - zoom1;
    var rotation1 = map1.getView().getRotation();
    var rotation2 = map2.getView().getRotation();
    this.dr = rotation2 - rotation1;
}

dlfViewerSyncControl.prototype.unsetSync = function () {
    this.sync = false;
}


class SyncViewsControl extends ol.control.Control {
    /**
     * @param {Object} [opt_options] Control options.
     */
    constructor(opt_options) {
        const options = opt_options || {};

        const button = document.createElement('button');
        button.innerHTML = 'SYNC';

        const buttonUnsync = document.createElement('button');
        buttonUnsync.innerHTML = 'UNSYNC';

        const element = document.createElement('div');
        element.className = 'sync-views ol-unselectable'; // ol-control
        element.appendChild(button);
        element.appendChild(buttonUnsync);

        super({
            element: element,
            target: options.target,
        });

        button.addEventListener('click', this.syncViews.bind(this), false);
        buttonUnsync.addEventListener('click', this.unsyncViews.bind(this), false);
    }

    toggleSyncViews() {
        tx_dlf_viewer.syncControl.setSync();
    }

    syncViews() {
        tx_dlf_viewer.syncControl.setSync();
    }

    unsyncViews() {
        tx_dlf_viewer.syncControl.unsetSync();
    }
}




