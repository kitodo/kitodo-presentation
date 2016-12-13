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
 * @TODO Trigger resize map event after fullscreen is toggled
 * @param {Object} settings
 *      {string=} div
 *      {Array.<?>} images
 *      {Array.<?>} fulltexts
 *      {Array.<?>} controls
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
     * Openlayers map object
     * @type {ol.Map|null}
     * @private
     */
    this.map = null;

    /**
     * Contains image information (e.g. URL, width, height)
     * @type {Array.<string>}
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
     * Fulltext information (e.g. URL)
     * @type {Array.<string|?>}
     * @private
     */
    this.fulltexts = dlfUtils.exists(settings.fulltexts) ? settings.fulltexts : [];

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
     * @type {Object|undefined}
     * @private
     */
    this.imageManipulationControl = undefined;

    this.init(dlfUtils.exists(settings.controls) ? settings.controls : []);
};

/**
 * Methods inits and binds the custom controls to the dlfViewer. Right now that are the
 * fulltext and the image manipulation control
 *
 * @param {Array.<string>} controlNames
 */
dlfViewer.prototype.addCustomControls = function(controlNames) {
	var fulltextControl = undefined,
		imageManipulationControl = undefined,
		images = this.images;

    // Adds fulltext behavior only if there is fulltext available and no double page
    // behavior is active
    if (this.fulltexts[0] !== undefined && this.fulltexts[0].url !== '' && this.images.length == 1) {
        fulltextControl = new dlfViewerFullTextControl(this.map, this.images[0], this.fulltexts[0].url);
    } else {
        $('#tx-dlf-tools-fulltext').remove();
    }


    //
    // Add image manipulation tool if container is added.
    //
    // It is important to know that the image manipulation tool uses a webgl renderer as basis. Therefor the
    // application has as first to check if the renderer is active. Further it has to check if cors supported through
    // image.
    //
    if ($('#tx-dlf-tools-imagetools').length > 0 && dlfUtils.isWebGLEnabled() && this.isCorsEnabled) {

        // should be called if cors is enabled
        imageManipulationControl = new dlfViewerImageManipulationControl({
            controlTarget: $('.tx-dlf-tools-imagetools')[0],
            layers: dlfUtils.createOl3Layers(images, '*'),
            map: this.map,
            view: dlfUtils.createOl3View(images)
        });

        // bind behavior of both together
        if (imageManipulationControl !== undefined && fulltextControl !== undefined) {
            $(imageManipulationControl).on("activate-imagemanipulation", $.proxy(fulltextControl.deactivate, fulltextControl));
            $(fulltextControl).on("activate-fulltext", $.proxy(imageManipulationControl.deactivate, imageManipulationControl));
        }

        // set on object scope
        this.imageManipulationControl = imageManipulationControl;

    } else if ($('#tx-dlf-tools-imagetools').length > 0) {

        // hide the element because the functionality is not supported through missing webgl or cors support.
        $('#tx-dlf-tools-imagetools').addClass('deactivate');

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
 * @return	void
 */
dlfViewer.prototype.addHighlightField = function(highlightField, imageIndex, width, height) {

    this.highlightFields.push(highlightField);

    this.highlightFieldParams = {
        index: imageIndex,
        width: width,
        height: height
    };

    if (this.map)
        this.displayHighlightWord();
};

/**
 * Creates OL3 controls
 * @param {Array.<string>} controlNames
 * @param {Array.<ol.layer.Layer> layers
 * @return {Array.<ol.control.Control>}
 * @private
 */
dlfViewer.prototype.createControls_ = function(controlNames, layers) {

    var controls = [];

    for (var i in controlNames) {

        if (controlNames[i] !== "") {

            switch(controlNames[i]) {

                case "OverviewMap":

                    controls.push(new ol.control.OverviewMap({
                        layers: layers
                    }));
                    break;

                case "ZoomPanel":

                    controls.push(new ol.control.Zoom());
                    break;

                default:

                    break;

            }
        }
    }

    return controls;
};

/**
 * Displayes highlight words
 */
dlfViewer.prototype.displayHighlightWord = function() {

    if (!dlfUtils.exists(this.highlightLayer)) {

        this.highlightLayer = new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.wordStyle
        });

        this.map.addLayer(this.highlightLayer);
    }

    // check if highlight by coords should be activate
    if (this.highlightFields.length > 0) {
        // clear in case of old displays
        this.highlightLayer.getSource().clear();

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

    // check if highlight by words is set
    var key = 'tx_dlf[highlight_word]',
        urlParams = dlfUtils.getUrlParams();

    if (urlParams != undefined && urlParams.hasOwnProperty(key) && this.fulltexts[0] !== undefined && this.fulltexts[0].url !== '' && this.images.length > 0) {
        var value = urlParams[key],
            values = value.split(';'),
            fulltextData = dlfViewerFullTextControl.fetchFulltextDataFromServer(this.fulltexts[0].url, this.images[0]),
            fulltextDataImageTwo = undefined;

        // check if there is another image / fulltext to look for
        if (this.images.length == 2 & this.fulltexts[1] !== undefined && this.fulltexts[1].url !== '') {
            var image = $.extend({}, this.images[1]);
            image.width = image.width + this.images[0].width;
            fulltextDataImageTwo = dlfViewerFullTextControl.fetchFulltextDataFromServer(this.fulltexts[1].url, this.images[1], this.images[0].width)
        }

        var stringFeatures = fulltextDataImageTwo === undefined ? fulltextData.getStringFeatures() :
          fulltextData.getStringFeatures().concat(fulltextDataImageTwo.getStringFeatures());
        values.forEach($.proxy(function(value) {
            var features = dlfUtils.searchFeatureCollectionForText(stringFeatures, value);
            if (features !== undefined) {
                for (var i = 0; i < features.length; i++) {
                    this.highlightLayer.getSource().addFeatures([features[i]]);
                }
            };
        }, this));
    };
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

    /**
     * Is cors enabled. Important information for correct renderer and layer initialization
     * @type {boolean}
     */
    this.isCorsEnabled = dlfUtils.isCorsEnabled(this.imageUrls);

    this.initLayer(this.imageUrls, this.isCorsEnabled)
        .done($.proxy(function(layers){

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
                    new ol.interaction.KeyboardZoom,
                    new ol.interaction.DragRotateAndZoom()
                ],
                // necessary for proper working of the keyboard events
                keyboardEventTarget: document,
                view: dlfUtils.createOl3View(this.images),
                renderer: 'canvas'
            });

            // Position image according to user preferences
            var lon = dlfUtils.getCookie("tx-dlf-pageview-centerLon"),
              lat = dlfUtils.getCookie("tx-dlf-pageview-centerLat"),
              zoom = dlfUtils.getCookie("tx-dlf-pageview-zoomLevel");
            if (!dlfUtils.isNullEmptyUndefinedOrNoNumber(lon) && !dlfUtils.isNullEmptyUndefinedOrNoNumber(lat) && !dlfUtils.isNullEmptyUndefinedOrNoNumber(zoom)) {
                // make sure, zoom center is on viewport
                var center = this.map.getView().getCenter();
                if ((lon < (2.2 * center[0])) && (lat < (2.2 * center[1]))) {
                    this.map.zoomTo([lon, lat], zoom);
                }
            }

            // highlight word in case a highlight field is registered
            this.displayHighlightWord();

            this.addCustomControls(controls);

            // trigger event after all has been initialize
            $(window).trigger("map-loadend", window);

            // append listener for saving view params in case of flipping pages
            $(window).unload($.proxy(function() {
                // check if image manipulation control exists and if yes deactivate it first for proper recognition of
                // the actual map view
                if (this.imageManipulationControl !== undefined && this.imageManipulationControl.isActive()) {
                    this.imageManipulationControl.deactivate();
                }

                var zoom = this.map.getZoom() !== undefined ? this.map.getZoom() : '',
                    center = this.map.getView().getCenter() !== undefined ? this.map.getView().getCenter() : ['', ''];

                // save actual map view parameters to cookie
                dlfUtils.setCookie('tx-dlf-pageview-zoomLevel', zoom);
                dlfUtils.setCookie('tx-dlf-pageview-centerLon', center[0]);
                dlfUtils.setCookie('tx-dlf-pageview-centerLat', center[1]);
            }, this));
        }, this));

};

/**
 * Function generate the ol3 layer objects for given image sources. Returns a promise.
 *
 * @param {Array.<{url: *, mimetype: *}>} imageSourceObjs
 * @param {boolean} isCorsEnabled
 * @return {jQuery.Deferred.<function(Array.<ol.layer.Layer>)>}
 * @private
 */
dlfViewer.prototype.initLayer = function(imageSourceObjs, isCorsEnabled) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred(),
      /**
       * @param {Array.<{src: *, width: *, height: *}>} imageSourceData
       * @param {Array.<ol.layer.Layer>} layers
       */
      resolveCallback = $.proxy(function(imageSourceData, layers) {
            this.images = imageSourceData;
            deferredResponse.resolve(layers);
        }, this),
      origin = isCorsEnabled ? '*' : undefined;

    dlfUtils.fetchImageData(imageSourceObjs)
      .done(function(imageSourceData) {
          resolveCallback(imageSourceData, dlfUtils.createOl3Layers(imageSourceData, origin));
      });

    return deferredResponse;
};
