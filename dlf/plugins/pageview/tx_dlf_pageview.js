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
     * ol3 controls which should be added to map
     * @type {Array.<?>}
     * @private
     */
    this.controls = dlfUtils.exists(settings.controls) ? this.createControls_(settings.controls) : [];

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

    this.init();
};

/**
 * Methods inits and binds the custom controls to the dlfViewer. Right now that are the
 * fulltext and the image manipulation control
 */
dlfViewer.prototype.addCustomControls = function() {
	var fulltextControl = undefined,
		imageManipulationControl = undefined, 
		images = this.images;
	
    // Adds fulltext behavior only if there are fulltext availabe and no double page 
    // behavior is active
    if (this.fulltexts[0] !== undefined && this.fulltexts[0] !== '' && this.images.length == 1)
    	fulltextControl = new dlfViewerFullTextControl(this.map, this.images[0], this.fulltexts[0]);
    
    // add image manipulation tool if container is added
    if ($('.tx-dlf-tools-imagetools').length > 0 && dlfUtils.isWebGLEnabled()){

    	imageManipulationControl = new dlfViewerImageManipulationControl({
    		target: $('.tx-dlf-tools-imagetools')[0],
    		layers: dlfUtils.createLayers(images),
    		mapContainer: this.div,
    		referenceMap: this.map,
    		view: dlfUtils.createView(images)
    	});
    	
    };
    
    // bind behavior of both together
    if (imageManipulationControl !== undefined && fulltextControl !== undefined) {
    	$(imageManipulationControl).on("activate-imagemanipulation", $.proxy(fulltextControl.deactivate, fulltextControl));
    	$(fulltextControl).on("activate-fulltext", $.proxy(imageManipulationControl.deactivate, imageManipulationControl));
    }
    
}

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
 * @return {Array.<ol.control.Control>}
 * @private
 */
dlfViewer.prototype.createControls_ = function(controlNames) {

    var controls = [];

    for (var i in controlNames) {

        if (controlNames[i] !== "") {

            switch(controlNames[i]) {

                case "OverviewMap":

                    controls.push(new ol.control.OverviewMap());
                    break;

                case "ZoomPanel" || "PanZoomBar" || "PanZoom":

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
 *
 */
dlfViewer.prototype.displayHighlightWord = function() {

    if (!dlfUtils.exists(this.highlightLayer)){

        this.highlightLayer = new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewer.style.wordStyle()
        });

    };

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
            feature = dlfUtils.scaleToImageSize([new ol.Feature(new ol.geom.Polygon(coordinates))], 
            		this.images[this.highlightFieldParams.index], 
            		this.highlightFieldParams.width,
                    this.highlightFieldParams.height,
                    offset);

        // add feature to layer and map
        this.highlightLayer.getSource().addFeatures(feature);
    };

    this.map.addLayer(this.highlightLayer);
};

/**
 * Register image files to load into map
 *
 * @param {Function} callback Callback which should be called after successful fetching
 */
dlfViewer.prototype.fetchImages = function(callback) {

    /**
     * This holds information about the loading state of the images
     * @type {Array.<number>}
     */
    var imagesLoaded = [0, this.imageUrls.length],
        img = [],
        images = [];

    for (var i in this.imageUrls) {

        // Prepare image loading.
        images[i] = {
            src: this.imageUrls[i],
            width: 0,
            height: 0
        };

        // Create new Image object.
        img[i] = new Image();

        // Register onload handler.
        img[i].onload = function() {

            for (var j in images) {

                if (images[j].src == this.src) {

                    // Add additional image data.
                    images[j] = {
                        src: this.src,
                        width: this.width,
                        height: this.height
                    };

                    break;

                }

            }

            // Count image as completely loaded.
            imagesLoaded[0]++;

            // Initialize OpenLayers map if all images are completely loaded.
            if (imagesLoaded[0] == imagesLoaded[1]) {

                callback(images);

            }

        };

        // Initialize image loading.
        img[i].src = this.imageUrls[i];

    }

};

/**
 * Start the init process of loading the map, etc.
 * @private
 */
dlfViewer.prototype.init = function(){

    /**
     * @param {Array.<{src: *, width: *, height: *}>} images
     */
    var init_ = $.proxy(function(images){

        // set image property of the object
        this.images = images,
        	renderer = 'canvas';
        
        // create map
        this.map = new ol.Map({
            layers: dlfUtils.createLayers(images, renderer),
            target: this.div,
            controls: this.controls,
                /*new ol.control.MousePosition({
                    coordinateFormat: ol.coordinate.createStringXY(4),
                    undefinedHTML: '&nbsp;'
                })*/
            interactions: [
                new ol.interaction.DragPan(),
                new ol.interaction.MouseWheelZoom(),
                new ol.interaction.KeyboardPan(),
                new ol.interaction.KeyboardZoom
            ],
            // necessary for proper working of the keyboard events
            keyboardEventTarget: document,
            view: dlfUtils.createView(images),
            renderer: renderer
        });

        // Position image according to user preferences
        var lon = dlfUtils.getCookie("tx-dlf-pageview-centerLon"),
            lat = dlfUtils.getCookie("tx-dlf-pageview-centerLat"),
            zoom = dlfUtils.getCookie("tx-dlf-pageview-zoomLevel");
        if (!dlfUtils.isNull(lon) && !dlfUtils.isNull(lat) && !dlfUtils.isNull(zoom)) {
            this.map.zoomTo([lon, lat], zoom);
        };

        // highlight word in case a highlight field is registered
        if (this.highlightFields.length)
            this.displayHighlightWord();
        
        this.addCustomControls();
    }, this);

    // init image loading process
    if (this.imageUrls.length > 0) {

        this.fetchImages(init_);

    };

};

/**
 * @const
 * @namespace
 */
dlfViewer.style = {};


/**
 * @return {ol.style.Style}
 */
dlfViewer.style.wordStyle = function() {

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