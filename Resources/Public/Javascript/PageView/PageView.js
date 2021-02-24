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
     * @type {Object.<{attributions: *, controls: *, controlLabels: *, controlTargets: *, controlTitles: *, target: *, images: *, useInternalProxy: *}>}
     */
    this.settings = {
        attributions: dlfUtils.exists(settings.attributions) ? settings.attributions : [''],
        controls: dlfUtils.exists(settings.controls) ? settings.controls : [],
        controlLabels: dlfUtils.exists(settings.controlLabels) ?  settings.controlLabels : {},
        controlTargets: dlfUtils.exists(settings.controlTargets) ? settings.controlTargets : {},
        controlTitles: dlfUtils.exists(settings.controlTitles) ? settings.controlTitles : {},
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

    if (!this.isCorsEnabled) {
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
        if (this.settings.controls.includes('Attribution')) {
            this.olx.controls.push(new ol.control.Attribution({
                collapsed: false,
                collapsible: true,
                label: this.settings.controlLabels.Attribution || undefined,
                target: this.settings.controlTargets.Attribution || undefined,
                tipLabel: this.settings.controlTitles.Attribution || undefined
            }));
        }
        if (this.settings.controls.includes('FullScreen')) {
            this.olx.controls.push(new ol.control.FullScreen({
                label: this.settings.controlLabels.FullScreen || undefined,
                labelActive: this.settings.controlLabels.FullScreenActive || undefined,
                target: this.settings.controlTargets.FullScreen || undefined,
                tipLabel: this.settings.controlTitles.FullScreen || undefined
            }));
        }
        if (this.settings.controls.includes('ImageManipulation')) {
            this.olx.controls.push(new ImageManipulationControl({
                // autoOpen: false,
                // className: 'ol-custom-image-manipulation',
                label: this.settings.controlLabels.ImageManipulation || undefined,
                target: this.settings.controlTargets.ImageManipulation || undefined,
                tipLabel: this.settings.controlTitles.ImageManipulation || undefined,
                title: this.settings.controlTitles.ImageManipulationDialogTitle || undefined
            }));
        }
        if (this.settings.controls.includes('Magnify')) {
            this.olx.controls.push(new MagnifyControl({
                // className: 'ol-custom-magnify',
                label: this.settings.controlLabels.Magnify || undefined,
                target: this.settings.controlTargets.Magnify || undefined,
                tipLabel: this.settings.controlTitles.Magnify || undefined
            }));
        }
        if (this.settings.controls.includes('OverviewMap')) {
            // Copy layers for overview map.
            var ovLayers = [];
            this.olx.layers.forEach((layer) => {
                ovLayers.push($.extend(true, {}, layer));
            });
            // Add buffer to extent for better fitting.
            var ovExtent = ol.extent.buffer(
                this.olx.extent,
                0.4 * Math.max(ol.extent.getWidth(this.olx.extent), ol.extent.getHeight(this.olx.extent))
            );
            this.olx.controls.push(new ol.control.OverviewMap({
                collapsed: false,
                collapseLabel: this.settings.controlLabels.OverviewMapCollapse || undefined,
                collapsible: true,
                label: this.settings.controlLabels.OverviewMap || undefined,
                layers: ovLayers,
                target: this.settings.controlTargets.OverviewMap || undefined,
                tipLabel: this.settings.controlTitles.OverviewMap || undefined,
                view: new ol.View({
                    center: ol.extent.getCenter(this.olx.extent),
                    extent: ovExtent,
                    projection: new ol.proj.Projection({
                        code: 'dlf-projection',
                        units: 'pixels',
                        extent: ovExtent
                    }),
                    showFullExtent: true
                })
            }));
        }
        if (this.settings.controls.includes('Rotate')) {
            this.olx.controls.push(new ol.control.Rotate({
                autoHide: false,
                label: this.settings.controlLabels.Rotate || undefined,
                target: this.settings.controlTargets.Rotate || undefined,
                tipLabel: this.settings.controlTitles.Rotate || undefined
            }));
            this.olx.controls.push(new RotateControl({
                // className: 'ol-custom-rotate',
                // delta: 0.5 * Math.PI, // ½π rad ≙ 90°
                // duration: 250,
                // rotateLeftClassName: 'ol-custom-rotate-left',
                rotateLeftLabel: this.settings.controlLabels.rotateLeft || undefined,
                rotateLeftTipLabel: this.settings.controlTitles.RotateLeft || undefined,
                // rotateRightClassName: 'ol-custom-rotate-right',
                rotateRightLabel: this.settings.controlLabels.rotateRight || undefined,
                rotateRightTipLabel: this.settings.controlTitles.RotateRight || undefined,
                target: this.settings.controlTargets.Rotate || undefined
            }));
        }
        if (this.settings.controls.includes('Zoom')) {
            this.olx.controls.push(new ol.control.Zoom({
                delta: 0.5,
                target: this.settings.controlTargets.Zoom || undefined,
                zoomInLabel: this.settings.controlLabels.zoomIn || undefined,
                zoomInTipLabel: this.settings.controlTitles.ZoomIn || undefined,
                zoomOutLabel: this.settings.controlLabels.ZoomOut || undefined,
                zoomOutTipLabel: this.settings.controlTitles.ZoomOut || undefined
            }));
        }
        if (this.settings.controls.includes('ZoomSlider')) {
            this.olx.controls.push(new ol.control.ZoomSlider({
                target: this.settings.controlTargets.ZoomSlider || undefined
            }));
        }
        if (this.settings.controls.includes('ZoomToExtent')) {
            this.olx.controls.push(new ol.control.ZoomToExtent({
                label: this.settings.controlLabels.ZoomToExtent || undefined,
                target: this.settings.controlTargets.ZoomToExtent || undefined,
                tipLabel: this.settings.controlTitles.ZoomToExtent || undefined
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
            image.extent = [offset, -image.height, image.width + offset, 0];
            var imageOptions = {
                extent: image.extent, // For IIIF, IIP and Zoomify sources.
                imageExtent: image.extent, // For static image sources.
                projection: new ol.proj.Projection({
                    code: 'dlf-projection',
                    units: 'pixels',
                    extent: image.extent
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
                this.olx.extent = ol.extent.extend(this.olx.extent, image.extent);
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
            maxResolution: 100, // Min zoom level is 1% of max resolution available.
            minResolution: 1, // Max zoom level is the max resolution available.
            projection: new ol.proj.Projection({
                code: 'dlf-projection',
                units: 'pixels',
                extent: this.olx.extent
            }),
            resolution: 100, // Initially set zoom to full extent.
            showFullExtent: true,
            zoomFactor: 1.5
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
