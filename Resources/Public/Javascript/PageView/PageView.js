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
 * The constructor for class dlfViewer
 *
 * @constructor
 *
 * @param {Object} settings
 * @param {string[]} settings.attributions
 * @param {string[]} settings.controls
 * @param {Object} settings.controlTargets
 * @param {string=} settings.target
 * @param {Object[]} settings.images
 * @param {boolean} settings.useInternalProxy
 */
var dlfViewer = function(settings) {
    /**
     * This holds the settings.
     * @public
     * @type {Object.<{attributions: *, controls: *, controlTargets: *, target: *, images: *, useInternalProxy: *}>}
     */
    this.settings = {
        attributions: dlfUtils.exists(settings.attributions) ? settings.attributions : [''],
        controls: dlfUtils.exists(settings.controls) ? settings.controls : [],
        controlTargets: dlfUtils.exists(settings.controlTargets) ? settings.controlTargets : {},
        target: dlfUtils.exists(settings.target) ? settings.target : 'tx-dlf-map',
        images: dlfUtils.exists(settings.images) ? settings.images : [],
        useInternalProxy: dlfUtils.exists(settings.useInternalProxy) ? true : false
    };

    // Do we have any images to display?
    if (this.settings.images.length < 1) {
        throw new Error('Missing image sources.');
    }

    /**
     * Is CORS enabled?
     * (It always is if we use the internal proxy.)
     * @public
     * @type {boolean}
     */
    this.isCorsEnabled = this.settings.useInternalProxy;
    if (this.isCorsEnabled !== true) {
        dlfUtils.isCorsEnabled(this.settings.images, this)
            .done((corsEnabled) => {
                this.isCorsEnabled = corsEnabled;
            });
    }

    /**
     * This holds the OpenLayers instances.
     * @public
     * @type {Object.<map: *, controls: *, extent: *, interactions: *, layers: *, sources: *, view: *>}
     */
    this.olx = {
        map: undefined,
        controls: undefined,
        extent: ol.extent.createEmpty(),
        interactions: undefined,
        layers: undefined,
        sources: undefined,
        view: undefined
    };

    // Get image metadata and start initialization.
    dlfUtils.getImageMetadata(this.settings.images, this)
        .done((imageMetadata) => {
            imageMetadata.forEach((metadata, index) => {
                this.settings.images[index].width = metadata.width;
                this.settings.images[index].height = metadata.height;
                this.settings.images[index].options = metadata.options;
            }, this);
            this.init();
        });
};

/**
 * Initialize the OpenLayers controls or return existing instance.
 * @return {Array.<{ol.control.Control}>}>}
 */
dlfViewer.prototype.getOLControls = function() {
    if (this.olx.controls === undefined) {
        this.olx.controls = [];
        if (this.settings.controls.includes("Attribution")) {
            this.olx.controls.push(new ol.control.Attribution({
                collapsed: false,
                collapsible: true,
                label: '\u00a9',
                target: this.settings.controlTargets.Attribution || undefined,
                tipLabel: ''
            }));
        }
        if (this.settings.controls.includes("FullScreen")) {
            this.olx.controls.push(new ol.control.FullScreen({
                target: this.settings.controlTargets.FullScreen || undefined,
                tipLabel: ''
            }));
        }
        if (this.settings.controls.includes("OverviewMap")) {
            // Copy layers for overview map.
            var ovLayers = [];
            this.olx.layers.forEach((layer) => {
                ovLayers.push($.extend(true, {}, layer));
            });
            this.olx.controls.push(new ol.control.OverviewMap({
                collapsed: false,
                collapsible: true,
                layers: ovLayers,
                target: this.settings.controlTargets.OverviewMap || undefined,
                tipLabel: '',
                view: new ol.View({
                    center: ol.extent.getCenter(this.olx.extent),
                    extent: this.olx.extent,
                    projection: new ol.proj.Projection({
                        code: 'dlf-projection',
                        units: 'pixels',
                        extent: this.olx.extent
                    }),
                    resolutions: [20],
                    showFullExtent: true
                })
            }));
        }
        if (this.settings.controls.includes("Rotate")) {
            this.olx.controls.push(new ol.control.Rotate({
                autoHide: false,
                target: this.settings.controlTargets.Rotate || undefined,
                tipLabel: ''
            }));
            this.olx.controls.push(new RotateControl({
                // className: 'ol-custom-rotate',
                // delta: 0.5 * Math.PI, // ½π rad ≙ 90°
                // duration: 250,
                // rotateLeftClassName: 'ol-custom-rotate-left',
                // rotateLeftLabel: '\u21ba',
                rotateLeftTipLabel: '',
                // rotateRightClassName: 'ol-custom-rotate-right',
                // rotateRightLabel: '\u21bb',
                rotateRightTipLabel: '',
                target: this.settings.controlTargets.Rotate || undefined
            }));
        }
        if (this.settings.controls.includes("Zoom")) {
            this.olx.controls.push(new ol.control.Zoom({
                delta: 0.5,
                target: this.settings.controlTargets.Zoom || undefined,
                zoomInTipLabel: '',
                zoomOutTipLabel: ''
            }));
        }
        if (this.settings.controls.includes("ZoomSlider")) {
            this.olx.controls.push(new ol.control.ZoomSlider({
                target: this.settings.controlTargets.ZoomSlider || undefined
            }));
        }
        if (this.settings.controls.includes("ZoomToExtent")) {
            this.olx.controls.push(new ol.control.ZoomToExtent({
                label: '\u26f6',
                target: this.settings.controlTargets.ZoomToExtent || undefined,
                tipLabel: ''
            }));
        }
    }
    return this.olx.controls;
};

/**
 * Initialize the OpenLayers interactions or return existing instance.
 * @return {Array.<{ol.interaction.Interaction}>}>}
 */
dlfViewer.prototype.getOLInteractions = function() {
    if (this.olx.interactions === undefined) {
        this.olx.interactions = [
            new ol.interaction.DragPan(),
            new ol.interaction.DragRotate({
                condition: ol.events.condition.shiftKeyOnly
            }),
            new ol.interaction.KeyboardPan(),
            new ol.interaction.KeyboardZoom(),
            new ol.interaction.MouseWheelZoom(),
            new ol.interaction.PinchRotate(),
            new ol.interaction.PinchZoom()
        ];
    }
    return this.olx.interactions;
};

/**
 * Initialize the OpenLayers layers or return existing instances.
 * @return {Array.<{ol.layer.Layer}>}
 */
dlfViewer.prototype.getOLLayers = function() {
    if (this.olx.layers === undefined) {
        this.olx.layers = [];
        this.olx.sources.forEach((source) => {
            var layer = undefined;
            switch (true) {
                case source instanceof ol.source.IIIF:
                case source instanceof ol.source.Zoomify:
                    layer = new ol.layer.Tile({source});
                    break;
                default:
                    layer = new ol.layer.Image({source});
                    break;
            }
            if (layer !== undefined) {
                this.olx.layers.push(layer);
            }
        });
    }
    return this.olx.layers;
};

/**
 * Initialize the OpenLayers map or return existing instance.
 * @return {ol.Map}
 */
dlfViewer.prototype.getOLMap = function() {
    if (this.olx.map === undefined) {
        this.olx.map = new ol.Map({
            controls: this.olx.controls,
            interactions: this.olx.interactions,
            keyboardEventTarget: document,
            layers: this.olx.layers,
            target: this.settings.target,
            view: this.olx.view
        });
    }
    return this.olx.map;
};

/**
 * Initialize the OpenLayers sources or return existing instances.
 * @return {Array.<{ol.source.Source}>}
 */
dlfViewer.prototype.getOLSources = function() {
    if (this.olx.sources === undefined) {
        this.olx.sources = [];
        var defaultOptions = {
            attributions: this.settings.attributions,
            crossOrigin: 'Anonymous',
            zDirection: -1
        };
        var offset = 0;
        this.settings.images.forEach((image) => {
            var extentOnMap = [offset, -image.height, image.width + offset, 0];
            var imageOptions = {
                extent: extentOnMap, // For IIIF, IIP and Zoomify sources.
                imageExtent: extentOnMap, // For static image sources.
                projection: new ol.proj.Projection({
                    code: 'dlf-projection',
                    units: 'pixels',
                    extent: extentOnMap
                })
            };
            var options = $.extend({}, defaultOptions, image.options, imageOptions);
            var source = undefined;
            switch (image.mimetype) {
                case dlfUtils.CUSTOM_MIMETYPE.IIIF:
                    source = new ol.source.IIIF(options);
                    break;
                case dlfUtils.CUSTOM_MIMETYPE.IIP:
                case dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY:
                    source = new ol.source.Zoomify(options);
                    break;
                default:
                    source = new ol.source.ImageStatic(options);
                    break;
            }
            if (source !== undefined) {
                this.olx.sources.push(source);
                // Add image extent to map extent.
                this.olx.extent = ol.extent.extend(this.olx.extent, extentOnMap);
                offset += image.width;
            }
        });
    }
    return this.olx.sources;
};

/**
 * Initialize the OpenLayers view or return existing instance.
 * @return {ol.View}
 */
dlfViewer.prototype.getOLView = function() {
    if (this.olx.view === undefined) {
        this.olx.view = new ol.View({
            center: ol.extent.getCenter(this.olx.extent),
            extent: this.olx.extent,
            maxZoom: 7,
            projection: new ol.proj.Projection({
                code: 'dlf-projection',
                units: 'pixels',
                extent: this.olx.extent
            }),
            showFullExtent: true,
            zoom: 1
        });
    }
    return this.olx.view;
};

/**
 * Initialize the viewer
 */
dlfViewer.prototype.init = function() {
    // Initialize OpenLayers map sources.
    this.olx.sources = this.getOLSources();
    // Initialize OpenLayers map layers.
    this.olx.layers = this.getOLLayers();
    // Initialize OpenLayers map view.
    this.olx.view = this.getOLView();
    // Initialize OpenLayers map controls.
    this.olx.controls = this.getOLControls();
    // Initialize OpenLayers map interactions.
    this.olx.interactions = this.getOLInteractions();
    // And finally initialize OpenLayers map object.
    this.olx.map = this.getOLMap();
};
