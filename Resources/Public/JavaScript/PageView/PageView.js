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
 * @typedef {object} LoadingIndicator
 * @property {(key: string, value: number, total: number) => void} progress
 * @property {(key: string) => void} indeterminate
 * @property {(key: string) => void} done
 */

/**
 * @typedef {{
 *  url: string;
 *  mimetype: string;
 * }} ResourceLocator
 *
 * @typedef {ResourceLocator} ImageDesc
 *
 * @typedef {ResourceLocator} FulltextDesc
 *
 * @typedef {{
 *  div: string;
 *  progressElementId?: string;
 *  images?: ImageDesc[] | [];
 *  fulltexts?: FulltextDesc[] | [];
 *  controls?: ('OverviewMap' | 'ZoomPanel')[];
 * }} DlfViewerConfig
 */

/**
 * @TODO Trigger resize map event after fullscreen is toggled
 * @param {DlfViewerConfig} settings
 * @constructor
 */
var dlfViewer = function(settings){

    /**
     * The element id of the map container
     * @type {string}
     * @private
     */
    this.div = dlfUtils.exists(settings.div) ? settings.div : "tx-dlf-map";

    /**
     * @type {Record<'overview-map', string>}
     * @private
     */
    this.dic = $.extend({
      'overview-map': 'Overview Map',
    }, dlfUtils.parseDataDic(document.getElementById(this.div)));

    /**
     * Openlayers map object
     * @type {ol.Map|null}
     * @private
     */
    this.map = null;

    /**
     * Contains image information (e.g. URL, width, height)
     * @type {DlfViewerConfig['images']}
     * @private
     */
    this.imageUrls = dlfUtils.exists(settings.images) ? settings.images : [];

    /**
     * Contains image information (e.g. URL, width, height)
     * @type {Array.<{src: *, width: *, height: *}>}
     * @private
     */
    this.images = [];

    /**
     * The <progress> element for loading indicator.
     * @type {LoadingIndicator}
     * @private
     */
    this.loadingIndicator = this.makeLoadingIndicator(settings.progressElementId);

    /**
     * Fulltext information (e.g. URL)
     * @type {Array.<string|?>}
     * @private
     */
    this.fulltexts = dlfUtils.exists(settings.fulltexts) ? settings.fulltexts : [];

    /**
     * Loaded fulltexts (as jQuery deferred object).
     * @type {JQueryStatic.Deferred[]}
     * @private
     */
    this.fulltextsLoaded_ = [];

    /**
     * IIIF annotation lists URLs for the current canvas
     * @type {Array.<string|?>}
     * @private
     */
    this.annotationContainers = dlfUtils.exists(settings.annotationContainers) ? settings.annotationContainers : [];

    /**
     * @type {Array.<number>}
     * @private
     */
    this.highlightFields = [];

    /**
     * @type {Object|undefined}
     * @private
     */
    this.highlightFieldParams = undefined;

    /**
     * @type {string|undefined}
     * @private
     */
    this.highlightCoordinates = null;

    /**
     * @type {string|undefined}
     * @private
     */
     this.highlightWords = null;

    /**
     * @type {Object|undefined}
     * @private
     */
    this.imageManipulationControl = undefined;

    /**
     * @type {Object|undefined}
     * @private
     */
    this.selection = undefined;

    /**
     * @type {Object|undefined}
     * @private
     */
    this.source = undefined;

    /**
     * @type {Object|undefined}
     * @private
     */
    this.selectionLayer = undefined;

    /**
     * @type {Object|undefined}
     * @private
     */
    this.draw = undefined;

    /**
     * @type {Object|null}
     * @private
     */
    this.source = null;

    /**
     * @type {Object|null}
     * @private
     */
    this.view = null;

    /**
     * @type {Object|null}
     * @private
     */
    this.ovView = null;

    /**
     * @type {Boolean|false}
     * @private
     */
    this.magnifierEnabled = false;

    /**
     * @type {Boolean|false}
     * @private
     */
    this.initMagnifier = false;

    /**
     * use internal proxy setting
     * @type {boolean}
     * @private
     */
    this.useInternalProxy = dlfUtils.exists(settings.useInternalProxy) ? settings.useInternalProxy : false;

    this.init(dlfUtils.exists(settings.controls) ? settings.controls : []);
};

/**
 *
 * @param {string | undefined}
 * @returns {LoadingIndicator}
 */
dlfViewer.prototype.makeLoadingIndicator = function (progressElementId) {
    // Show progress bar only if total loading time is expected to be at least 1 second
    const MAX_SECONDS = 1;

    // Query progress element on demand because it may only become available at a later point (Zeitungsportal)
    return {
        startTime: null,
        values: {},
        finishedTotal: 0,
        update() {
            var progressElement = document.getElementById(progressElementId);
            if (!(progressElement instanceof HTMLProgressElement)) {
                return;
            }

            var downloads = Object.values(this.values);
            if (downloads.length === 0) {
                // Download has finished
                this.startTime = null;
                this.values = {};
                this.finishedTotal = 0;
                progressElement.classList.remove('loading');
                return;
            }

            var valueAbs = 0;
            var total = 0;
            downloads.forEach(function (entry) {
                valueAbs += entry.value;
                total += entry.total;
            });

            if (total === 0) {
                // All running downloads are indeterminate, so make progress bar indeterminate
                progressElement.removeAttribute('value');
                progressElement.classList.add('loading');
            } else {
                var valueRel = (valueAbs + this.finishedTotal) / (total + this.finishedTotal);
                progressElement.value = valueRel * progressElement.max;

                if (this.startTime === null) {
                    this.startTime = window.performance.now();
                } else {
                    const diffMs = window.performance.now() - this.startTime;
                    const expectedSeconds = (diffMs / 1000) / valueRel;
                    if (expectedSeconds > MAX_SECONDS) {
                        progressElement.classList.add('loading');
                    }
                }
            }
        },
        indeterminate(key) {
            this.progress(key, 0, 0);
        },
        progress(key, value, total) {
            this.values[key] = { value, total };
            this.update();
        },
        done(key) {
            if (this.values[key]) {
                this.finishedTotal += this.values[key].total;
                delete this.values[key];
                this.update();
            }
        },
    };
};

/**
 * Get number of shown pages. Typically 1 (single page) or 2 (double page mode).
 *
 * @returns {number}
 */
dlfViewer.prototype.countPages = function () {
    return this.imageUrls.length;
};

/**
 * Methods inits and binds the custom controls to the dlfViewer. Right now that are the
 * fulltext and the image manipulation control
 *
 * @param {Array.<string>} controlNames
 */
dlfViewer.prototype.addCustomControls = function() {
    var fulltextControl = undefined,
        fulltextDownloadControl = undefined,
        annotationControl = undefined,
        imageManipulationControl = undefined,
        images = this.images;

    // Adds fulltext behavior and download only if there is fulltext available and no double page
    // behavior is active
    if (this.fulltextsLoaded_[0] !== undefined && this.images.length === 1) {
        fulltextControl = new dlfViewerFullTextControl(this.map);
        fulltextDownloadControl = new dlfViewerFullTextDownloadControl(this.map);

        this.fulltextsLoaded_[0]
            .then(function (fulltextData) {
                fulltextControl.loadFulltextData(fulltextData);
                fulltextDownloadControl.setFulltextData(fulltextData);
            })
            .catch(function () {
                fulltextControl.deactivate();
            });
    } else {
        $('#tx-dlf-tools-fulltext').remove();
    }

    if (this.annotationContainers[0] !== undefined && this.annotationContainers[0].annotationContainers !== undefined
        && this.annotationContainers[0].annotationContainers.length > 0 && this.images.length === 1) {
        // Adds annotation behavior only if there are annotations available and view is single page
        annotationControl = new DlfAnnotationControl(this.map, this.images[0], this.annotationContainers[0]);
        if (fulltextControl !== undefined) {
            $(fulltextControl).on("activate-fulltext", $.proxy(annotationControl.deactivate, annotationControl));
            $(annotationControl).on("activate-annotations", $.proxy(fulltextControl.deactivate, fulltextControl));
        }
    }
    else {
        $('#tx-dlf-tools-annotations').remove();
    }

    //
    // Add image manipulation tool if container is added.
    //
    if ($('#tx-dlf-tools-imagetools').length > 0) {

        // should be called if cors is enabled
        imageManipulationControl = new dlfViewerImageManipulationControl({
            controlTarget: $('.tx-dlf-tools-imagetools')[0],
            map: this.map,
        });

        // bind behavior of both together
        if (fulltextControl !== undefined) {
            $(imageManipulationControl).on("activate-imagemanipulation", $.proxy(fulltextControl.deactivate, fulltextControl));
            $(fulltextControl).on("activate-fulltext", $.proxy(imageManipulationControl.deactivate, imageManipulationControl));
        }
        if (annotationControl !== undefined) {
            $(imageManipulationControl).on("activate-imagemanipulation", $.proxy(annotationControl.deactivate, annotationControl));
            $(annotationControl).on("activate-annotations", $.proxy(imageManipulationControl.deactivate, imageManipulationControl));
        }

        // set on object scope
        this.imageManipulationControl = imageManipulationControl;

    }
};

/**
 * Add highlight field
 *
 * @param {Array.<number>} highlightField
 * @param {number} imageIndex
 * @param {number} width
 * @param {number} height
 *
 * @return void
 */
dlfViewer.prototype.addHighlightField = function(highlightField, imageIndex, width, height) {

    this.highlightFields.push(highlightField);

    this.highlightFieldParams = {
        index: imageIndex,
        width,
        height
    };

    if (this.map) {
        this.displayHighlightWord();
    }
};

/**
 * Creates OpenLayers controls
 * @param {Array.<string>} controlNames
 * @param {Array.<ol.layer.Layer>} layers
 * @return {Array.<ol.control.Control>}
 * @private
 */
dlfViewer.prototype.createControls_ = function(controlNames, layers) {
    var controls = [];

    for (var i in controlNames) {
        if (controlNames.hasOwnProperty(i)) {
            var control = this.createControl(controlNames[i], layers);
            if (control !== null) {
                controls.push(control);
            }
        }
    }

    return controls;
};

/**
 * Create OpenLayers control of the specified key.
 * If `null` is returned, the control is omitted.
 *
 * @param {string} controlName
 * @param {Array.<ol.layer.Layer>} layers
 * @return {ol.control.Control | null}
 * @protected
 */
dlfViewer.prototype.createControl = function(controlName, layers) {
    switch (controlName) {
        case "OverviewMap": {
            var extent = ol.extent.createEmpty();
            for (let i = 0; i < this.images.length; i++) {
                ol.extent.extend(extent, [0, -this.images[i].height, this.images[i].width, 0]);
            }

            var ovExtent = ol.extent.buffer(
                extent,
                1 * Math.max(ol.extent.getWidth(extent), ol.extent.getHeight(extent))
            );

            return new ol.control.OverviewMap({
                tipLabel: this.dic['overview-map'],
                layers: layers.map(dlfUtils.cloneOlLayer),
                view: new ol.View({
                    center: ol.extent.getCenter(extent),
                    extent: ovExtent,
                    projection: new ol.proj.Projection({
                        code: 'kitodo-image',
                        units: 'pixels',
                        extent: ovExtent
                    }),
                    showFullExtent: false
                })
            });
        }

        case "ZoomPanel":
            return new ol.control.Zoom();

        default:
            return null;
    }
};

/**
 * Forwards the search to dlfUtils.searchFeatureCollectionForWords
 *
 * @param {Array.<ol.Feature>} stringFeatures - Array of features containing text information
 * @param {string} value - Search term
 * @returns {Array.<ol.Feature>|undefined} Array of OpenLayers features containing found words
 * @see dlfUtils.searchFeatureCollectionForWords
 */
dlfViewer.prototype.searchFeatures = function(stringFeatures, value) {
    if (this.highlightWords !== null) {
        return dlfUtils.searchFeatureCollectionForWords(stringFeatures, value);
    }

    if (this.highlightCoordinates !== null) {
        return dlfUtils.searchFeatureCollectionForCoordinates(stringFeatures, value);
    }
};

/**
 * Displays highlight words
 */
dlfViewer.prototype.displayHighlightWord = function(highlightCoordinates = null) {
    if (highlightCoordinates != null) {
        this.highlightCoordinates = highlightCoordinates;
    }

    // extract highlight words from URL
    if (this.highlightCoordinates === null) {
        this.highlightWords = dlfUtils.getUrlParam('tx_dlf[highlight_word]');
    }

    if (!dlfUtils.exists(this.highlightLayer)) {

        this.highlightLayer = new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOLStyles.wordStyle
        });

        this.map.addLayer(this.highlightLayer);
    }

    // clear in case of old displays
    this.highlightLayer.getSource().clear();

    // check if highlight by coords should be activate
    if (this.highlightFields.length > 0) {
        // create features and scale it down
        for (var i = 0; i < this.highlightFields.length; i++) {

            var field = this.highlightFields[i],
              coordinates = [[
                  [field[0], field[1]],
                  [field[2], field[1]],
                  [field[2], field[3]],
                  [field[0], field[3]],
                  [field[0], field[1]],
              ]],
              offset = this.highlightFieldParams.index === 1 ? this.images[0].width : 0;
            var feature = dlfUtils.scaleToImageSize([new ol.Feature(new ol.geom.Polygon(coordinates))],
              this.images[this.highlightFieldParams.index],
              this.highlightFieldParams.width,
              this.highlightFieldParams.height,
              offset);

            // add feature to layer and map
            this.highlightLayer.getSource().addFeatures(feature);
        }
    }

    if (this.highlightWords !== null || this.highlightCoordinates !== null) {
        const self = this;
        const values = this.getHighlightValues();

        $.when.apply($, this.fulltextsLoaded_)
            .done((fulltextData, fulltextDataImageTwo) => {
                const stringFeatures = [];

                [fulltextData, fulltextDataImageTwo].forEach(data => {
                    if (data !== undefined) {
                        Array.prototype.push.apply(stringFeatures, data.getStringFeatures());
                    }
                });

                values.forEach((value) => {
                    const features = this.searchFeatures(stringFeatures, value);
                    if (features !== undefined) {
                        for (let i = 0; i < features.length; i++) {
                            self.highlightLayer.getSource().addFeatures([features[i]]);
                        }
                    }
                });
            });
    };
};

dlfViewer.prototype.getHighlightValues = function() {

    if (this.highlightWords !== null) {
        return decodeURIComponent(this.highlightWords).split(';');
    }

    if (this.highlightCoordinates !== null) {
        return decodeURIComponent(this.highlighCoordinates).split(';');
    }
};

/**
 * Start the init process of loading the map, etc.
 *
 * @param {Array.<string>} controlNames
 * @private
 */
dlfViewer.prototype.init = function(controlNames) {

    if (this.imageUrls.length <= 0)
        throw new Error('Missing image source objects.');

    this.initLayer(this.imageUrls)
        .done($.proxy(function(layers){

            // Initiate loading fulltexts
            this.initLoadFulltexts();

            var controls = controlNames.length > 0 || controlNames[0] === ""
                ? this.createControls_(controlNames, layers)
                : [];
                //: [ new ol.control.MousePosition({
                //        coordinateFormat: ol.coordinate.createStringXY(4),
                //        undefinedHTML: '&nbsp;'
                //    })];

            // create map
            this.map = new ol.Map({
                layers: layers,
                target: this.div,
                controls: controls,
                interactions: [
                    new ol.interaction.DragRotate(),
                    new ol.interaction.DragPan(),
                    new ol.interaction.DragZoom(),
                    new ol.interaction.PinchRotate(),
                    new ol.interaction.PinchZoom(),
                    new ol.interaction.MouseWheelZoom(),
                    new ol.interaction.KeyboardPan(),
                    new ol.interaction.KeyboardZoom(),
                    new ol.interaction.DragRotateAndZoom()
                ],
                // necessary for proper working of the keyboard events
                keyboardEventTarget: document,
                view: dlfUtils.createOlView(this.images),
            });

            // Position image according to user preferences
            var lonCk = dlfUtils.getCookie("tx-dlf-pageview-centerLon"),
              latCk = dlfUtils.getCookie("tx-dlf-pageview-centerLat"),
              zoomCk = dlfUtils.getCookie("tx-dlf-pageview-zoomLevel");
            if (!dlfUtils.isNullEmptyUndefinedOrNoNumber(lonCk) && !dlfUtils.isNullEmptyUndefinedOrNoNumber(latCk) && !dlfUtils.isNullEmptyUndefinedOrNoNumber(zoomCk)) {
                var lon = Number(lonCk),
                  lat = Number(latCk),
                  zoom = Number(zoomCk);

                // make sure, zoom center is on viewport
                var center = this.map.getView().getCenter();
                if ((lon < (2.2 * center[0])) && (lat < (-0.2 * center[1])) && (lat > (2.2 * center[1]))) {
                    this.map.zoomTo([lon, lat], zoom);
                }
            }

            this.addCustomControls();

            // highlight word in case a highlight field is registered
            this.displayHighlightWord();

            // trigger event after all has been initialize
            $(window).trigger("map-loadend", window);

            // append listener for saving view params in case of flipping pages
            $(window).on("unload", $.proxy(function() {
                // check if image manipulation control exists and if yes deactivate it first for proper recognition of
                // the actual map view
                if (this.imageManipulationControl !== undefined && this.imageManipulationControl.isActive()) {
                    this.imageManipulationControl.deactivate();
                }

                var zoom = this.map.getZoom() !== undefined ? this.map.getZoom() : '',
                    center = this.map.getView().getCenter() !== undefined ? this.map.getView().getCenter() : ['', ''];

                // save actual map view parameters to cookie
                dlfUtils.setCookie('tx-dlf-pageview-zoomLevel', zoom, "lax");
                dlfUtils.setCookie('tx-dlf-pageview-centerLon', center[0], "lax");
                dlfUtils.setCookie('tx-dlf-pageview-centerLat', center[1], "lax");
            }, this));
        }, this));
        this.source = new ol.source.Vector();
        // crop selection style
        this.selection = new ol.interaction.DragBox({
            condition: ol.events.condition.noModifierKeys,
            style: new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: [0, 0, 255, 1]
                })
            })
        });

        this.initCropping();
};

/**
 * Generate the OpenLayers layer objects for given image sources. Returns a promise / jQuery deferred object.
 *
 * @param {ImageDesc[]} imageSourceObjs
 * @return {jQuery.Deferred.<function(Array.<ol.layer.Layer>)>}
 * @private
 */
dlfViewer.prototype.initLayer = function(imageSourceObjs) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred(),
      /**
       * @param {Array.<{src: *, width: *, height: *}>} imageSourceData
       * @param {Array.<ol.layer.Layer>} layers
       */
      resolveCallback = $.proxy(function(imageSourceData, layers) {
            this.images = imageSourceData;
            deferredResponse.resolve(layers);
        }, this);

    dlfUtils.fetchImageData(imageSourceObjs, this.loadingIndicator)
      .done(function(imageSourceData) {
          resolveCallback(imageSourceData, dlfUtils.createOlLayers(imageSourceData));
      });

    return deferredResponse;
};

/**
 * Start loading fulltexts and store them to `fulltextsLoaded_` (as jQuery deferred objects).
 *
 * @private
 */
dlfViewer.prototype.initLoadFulltexts = function () {
    var cnt = Math.min(this.fulltexts.length, this.images.length);
    var xOffset = 0;
    for (var i = 0; i < cnt; i++) {
        var fulltext = this.fulltexts[i];
        var image = this.images[i];

        if (dlfUtils.isFulltextDescriptor(fulltext)) {
            this.fulltextsLoaded_[i] = dlfFullTextUtils.fetchFullTextDataFromServer(fulltext.url, image, xOffset);
        }

        xOffset += image.width;
    }
};

dlfViewer.prototype.degreeToRadian = function (degree) {
    return degree * (Math.PI / 180);
};

dlfViewer.prototype.radianToDegree = function (radian) {
    return radian * (180 / Math.PI);
};

/**
 * activates the crop selection
 */
dlfViewer.prototype.activateSelection = function () {
    var viewerObject = this;

    // remove all features
    viewerObject.resetCropSelection();

    // add selection layer and crop interaction
    this.map.addLayer(this.selectionLayer);
    this.map.addInteraction(this.draw);
};

/**
 * reset the crop selection
 */
dlfViewer.prototype.resetCropSelection = function () {
    this.map.removeLayer(this.selectionLayer);
    this.source.clear();
    this.setNewCropDrawer(this.source);
};

/**
 * initialise crop selection
 */
dlfViewer.prototype.initCropping = function () {
    var viewerObject = this;

    var source = new ol.source.Vector({wrapX: false});

    this.selectionLayer = new ol.layer.Vector({
        source: source
    });

    value = 'LineString';
    maxPoints = 2;
    geometryFunction = function(coordinates, geometry) {
        var start = coordinates[0];
        var end = coordinates[1];
        var geomCoordinates = [
            [start, [start[0], end[1]], end, [end[0], start[1]], start]
        ];
        if (geometry) {
            geometry.setCoordinates(geomCoordinates);
        } else {
            geometry = new ol.geom.Polygon(geomCoordinates);
        }

        // add to basket button
        var extent = geometry.getExtent();
        var imageExtent = viewerObject.map.getLayers().item(0).getSource().getProjection().getExtent();

        var pixel = ol.extent.getIntersection(imageExtent, extent);
        var rotation = viewerObject.map.getView().getRotation();

        // fill form with coordinates
        $('#addToBasketForm #startX').val(Math.round(pixel[0]));
        $('#addToBasketForm #startY').val(Math.round(pixel[1]));
        $('#addToBasketForm #endX').val(Math.round(pixel[2]));
        $('#addToBasketForm #endY').val(Math.round(pixel[3]));
        $('#addToBasketForm #rotation').val(Math.round(viewerObject.radianToDegree(rotation)));

        return geometry;
    };

    this.setNewCropDrawer(source);
};

/**
 * set the draw interaction for crop selection
 */
dlfViewer.prototype.setNewCropDrawer = function (source) {
    viewerObject = this;
    this.draw = new ol.interaction.Draw({
        source: source,
        type: /** @type {ol.geom.GeometryType} */ (value),
        geometryFunction: geometryFunction,
        maxPoints: maxPoints
    });

    // reset crop interaction
    this.draw.on('drawend', function (event) {
        viewerObject.map.removeInteraction(viewerObject.draw);
    });

    this.selectionLayer = new ol.layer.Vector({
        source: source
    });
};

/**
 * add magnifier map
 */
dlfViewer.prototype.addMagnifier = function (rotation) {

    //magnifier map
    var extent = [0, 0, 1000, 1000];

    var layerProj = new ol.proj.Projection({
        code: 'kitodo-image',
        units: 'pixels',
        extent: extent
    });

    this.ovView = new ol.View({
        constrainRotation: false,
        projection: layerProj,
        center: ol.extent.getCenter(extent),
        zoom: 3,
        rotation: rotation,
    });

    this.ovMap = new ol.Map({
        target: 'ov_map',
        view: this.ovView,
        controls: [],
        interactions: []
    });

    this.ovMap.addLayer(dlfUtils.cloneOlLayer(this.map.getLayers().getArray()[0]));

    var mousePosition = null;
    var dlfViewer = this;
    var ovMap = this.ovMap;

    this.map.on('pointermove', function (evt) {
        mousePosition = dlfViewer.map.getEventCoordinate(evt.originalEvent);
        dlfViewer.ovView.setCenter(mousePosition);
    });

    var adjustViews = function(sourceView, destMap) {
            var rotateDiff = sourceView.getRotation() !== destMap.getView().getRotation();
            var centerDiff = sourceView.getCenter() !== destMap.getView().getCenter();

            if (rotateDiff || centerDiff) {
                destMap.getView().setRotation(sourceView.getRotation());
            }
        },
        adjustViewHandler = function(event) {
            adjustViews(event.target, ovMap);
        };

    this.map.getView().on('change:rotation', adjustViewHandler, this.map);
    adjustViews(this.map.getView(), this.ovMap);

    this.initMagnifier = true;
};

/**
 * activates the magnifier map
 */
dlfViewer.prototype.activateMagnifier = function () {
    if (!this.initMagnifier) {
        var rotation = this.map.getView().getRotation();
        this.addMagnifier(rotation);
    }

    if (!this.magnifierEnabled) {
        $('#ov_map').show();
        this.magnifierEnabled = true;
    } else {
        $('#ov_map').hide();
        this.magnifierEnabled = false;
    }
};

/**
 * @constructor
 * @extends {ol.interaction.Pointer}
 */
function Drag() {

    ol.interaction.Pointer.call(this, {
        handleDownEvent: Drag.prototype.handleDownEvent,
        handleDragEvent: Drag.prototype.handleDragEvent,
        handleMoveEvent: Drag.prototype.handleMoveEvent,
        handleUpEvent: Drag.prototype.handleUpEvent
    });

    /**
     * @type {ol.Pixel}
     * @private
     */
    this.coordinate_ = null;

    /**
     * @type {string|undefined}
     * @private
     */
    this.cursor_ = 'pointer';

    /**
     * @type {ol.Feature}
     * @private
     */
    this.feature_ = null;

    /**
     * @type {string|undefined}
     * @private
     */
    this.previousCursor_ = undefined;

};
