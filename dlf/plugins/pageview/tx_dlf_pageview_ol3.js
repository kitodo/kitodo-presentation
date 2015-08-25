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
var dlfViewerOl3 = function(settings){

    console.log(settings);

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
     * Original image information (e.g. width, height)
     * @type {Array.<?>}
     * @private
     */
    this.origImages = [];

    /**
     * ol3 controls which should be added to map
     * @type {Array.<?>}
     * @private
     */
    //this.controls = dlfUtils.exists(settings.controls) ? settings.controls : [];

    /**
     * Offset for the second image
     * @type {number}
     * @private
     */
    this.offset = 0;

    /**
     * Fulltexts together with the coordinates of the textblocks
     * @type {Array.<Array.<ol.Feature>>}
     * @private
     */
    this.fullTextCoordinates = [];

    /**
     * Language token
     * @type {string}
     * @private
     */
    this.lang = dlfUtils.exists(settings.lang) ? settings.lang : 'de';

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.textBlockLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOl3.style.defaultStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.highlightLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOl3.style.hoverStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.selectLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOl3.style.selectStyle()
    });

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
 * Add highlight field
 *
 * @param {Array.<number>} highlightField
 * @param {number} imageIndex
 * @param {number} width
 * @param {number} height
 *
 * @return	void
 */
dlfViewerOl3.prototype.addHighlightField = function(highlightField, imageIndex, width, height) {

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
 *
 */
dlfViewerOl3.prototype.displayHighlightWord = function() {

    if (!dlfUtils.exists(this.highlighLayer)){

        this.highlighLayer = new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOl3.style.wordStyle()
        });

    };

    // clear in case of old displays
    this.highlighLayer.getSource().clear();


    // set origimage with highlightFieldParams
    if (!this.origImages.length && dlfUtils.exists(this.highlightFields)) {

        this.setOrigImage(this.highlightFieldParams.index, this.highlightFieldParams.width,
            this.highlightFieldParams.height);

    }


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
            feature = this.scaleDown(0, [new ol.Feature(new ol.geom.Polygon(coordinates))]);

        // add feature to layer and map
        this.highlighLayer.getSource().addFeatures(feature);
    };

    this.map.addLayer(this.highlighLayer);
};

/**
 * Activate Fulltext Features
 */
dlfViewerOl3.prototype.enableFulltextSelect = function() {

    // Create image layers.
    for (var i in this.images) {

        if (this.fulltexts[i]) {

            this.fullTextCoordinates[i] = this.loadALTO(this.fulltexts[i]);

        }

    }

    // add fulltext layers if we have fulltexts to show
    if (this.fullTextCoordinates.length > 0) {

        for (var i in this.images) {

            // extract the parent geometry and use it for setting the
            // correct original image options / scale
            var pageOrPrintSpaceFeature = this.fullTextCoordinates[i][0];
            this.setOrigImage(i, pageOrPrintSpaceFeature.get('width') , pageOrPrintSpaceFeature.get('height'));

            var textBlockCoordinates = this.scaleDown(i, pageOrPrintSpaceFeature.get('features'));
            for (var j in textBlockCoordinates) {

                this.textBlockLayer.getSource().addFeature(textBlockCoordinates[j]);

            }

        }

        if (dlfUtils.exists(this.textBlockLayer)) {

            // add layers to map
            this.map.addLayer(this.textBlockLayer);
            this.map.addLayer(this.highlightLayer);
            this.map.addLayer(this.selectLayer);

            // show fulltext container
            $("#tx-dlf-fulltextselection").show();

        }

    }

};

/**
 * Register image files to load into map
 *
 * @param {Function} callback Callback which should be called after successful fetching
 */
dlfViewerOl3.prototype.fetchImages = function(callback) {

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
dlfViewerOl3.prototype.init = function(){

    /**
     * @param {Array.<{src: *, width: *, height: *}>} images
     */
    var init_ = $.proxy(function(images){

        // set image property of the object
        this.images = images;

        // create image layers
        var layers = [];
        for (var i = 0; i < images.length; i++) {

            var layerExtent = i === 0 ? [0 , 0, images[i].width, images[i].height] :
                [images[i-1].width , 0, images[i].width + images[i-1].width, images[i].height]

            var layerProj = new ol.proj.Projection({
                    code: 'goobi-image',
                    units: 'pixels',
                    extent: layerExtent
                }),
                layer = new ol.layer.Image({
                    source: new ol.source.ImageStatic({
                        url: images[i].src,
                        projection: layerProj,
                        imageExtent: layerExtent
                    })
                });
            layers.push(layer);
        };

        // create map extent
        var maxx = images.length === 1 ? images[0].width : images[0].width + images[1].width,
            maxy = images.length === 1 ? images[0].height : Math.max(images[0].height, images[1].height),
            mapExtent = [0, 0, maxx, maxy],
            mapProj = new ol.proj.Projection({
                code: 'goobi-image',
                units: 'pixels',
                extent: mapExtent
            });

        // create map
        this.map = new ol.Map({
            layers: layers,
            target: this.div,
            controls: [
                /*new ol.control.MousePosition({
                    coordinateFormat: ol.coordinate.createStringXY(4),
                    undefinedHTML: '&nbsp;'
                })*/
            ],
            interactions: [
                new ol.interaction.DragPan(),
                new ol.interaction.MouseWheelZoom(),
                new ol.interaction.KeyboardPan(),
                new ol.interaction.KeyboardZoom
            ],
            // necessary for proper working of the keyboard events
            keyboardEventTarget: document,
            view: new ol.View({
                projection: mapProj,
                center: ol.extent.getCenter(mapExtent),
                zoom: 0,
                maxZoom: 8,
                extent: mapExtent
            })
        });

        // Position image according to user preferences
        var lon = dlfUtils.getCookie("tx-dlf-pageview-centerLon"),
            lat = dlfUtils.getCookie("tx-dlf-pageview-centerLat"),
            zoom = dlfUtils.getCookie("tx-dlf-pageview-zoomLevel");
        if (!dlfUtils.isNull(lon) && !dlfUtils.isNull(lat) && !dlfUtils.isNull(zoom)) {
            this.map.zoomTo([lon, lat], zoom);
        };

        //
        // couple fulltext event behavior with map
        //

        var featureClicked;
        this.map.on('click', function(event) {

            var feature = this.map.forEachFeatureAtPixel(event['pixel'], function(feature, layer) {
                return feature;
            });

            // highlight features
            if (feature !== featureClicked) {

                if (featureClicked) {

                    this.selectLayer.getSource().removeFeature(featureClicked);

                }

                if (feature) {

                    this.selectLayer.getSource().addFeature(feature);

                }

                featureClicked = feature;

            }

            if (dlfUtils.exists(feature))
                this.showFulltext(feature);

        }, this);

        var highlightFeature;
        this.map.on('pointermove', function(event) {

            if (event['dragging']) {
                return;
            };

            var feature = this.map.forEachFeatureAtPixel(event['pixel'], function(feature, layer) {
                return feature;
            });

            // highlight features
            if (feature !== highlightFeature) {

                if (highlightFeature) {

                    this.highlightLayer.getSource().removeFeature(highlightFeature);

                }

                if (feature) {

                    this.highlightLayer.getSource().addFeature(feature);

                }

                highlightFeature = feature;

            }

        }, this);

        // keep fulltext feature active
        var isFulltextActive = dlfUtils.getCookie("tx-dlf-pageview-fulltext-select");
        if (isFulltextActive == 'enabled') {

            this.enableFulltextSelect();

        };

        // highlight word in case a highlight field is registered
        if (this.highlightFields.length)
            this.displayHighlightWord();

    }, this);

    // init image loading process
    if (this.imageUrls.length > 0) {

        this.fetchImages(init_);

    };



};

/**
 * Activate Fulltext Features
 */
dlfViewerOl3.prototype.toggleFulltextSelect = function() {

    var isFulltextActive = dlfUtils.getCookie("tx-dlf-pageview-fulltext-select");

    if (isFulltextActive == 'enabled') {

        this.disableFulltextSelect();
        dlfUtils.setCookie("tx-dlf-pageview-fulltext-select", 'disabled');

    } else {

        this.enableFulltextSelect();
        dlfUtils.setCookie("tx-dlf-pageview-fulltext-select", 'enabled');

    }

};

/**
 * Scales down the given features geometrys
 *
 * @param {number} image
 * @param {Array.<ol.Feature>} features
 */
dlfViewerOl3.prototype.scaleDown = function(image, features) {

    if (this.origImages.length > 1 && image == 1) {

        var scale = this.origImages[1].scale;
        var height = this.images[1].height;
        var offset = this.images[0].width;

    } else {

        var scale = this.origImages[0].scale;
        var height = this.images[0].height;
        var offset = 0;

    }

    // do a rescaling
    for (var i in features) {

        var oldCoordinates = features[i].getGeometry().getCoordinates()[0],
            newCoordinates = [];


        for (var j = 0; j < oldCoordinates.length; j++) {
            newCoordinates.push([offset + (scale * oldCoordinates[j][0]), height - (scale * oldCoordinates[j][1])]);
        }

        features[i].setGeometry(new ol.geom.Polygon([newCoordinates]));
    }

    return features;
};

/**
 * Set Original Image Size
 *
 * @param	integer image number
 * @param	integer width
 * @param	integer height
 *
 * @return	void
 */
dlfViewerOl3.prototype.setOrigImage = function(i, width, height) {

    if (width && height) {

        this.origImages[i] = {
            'width': width,
            'height': height,
            'scale': this.images[i].width/width,
        };

    }

};


/**
 * Read ALTO file and return found words
 *
 * @param {Object} url
 * @return  {Array.<ol.Feature>}
 */
dlfViewerOl3.prototype.loadALTO = function(url){

    var request = $.ajax({
        url: url,
        async: false
    });

    var format = new ol.format.ALTO();

    if (request.responseXML)
        var wordCoords = format.readFeatures(request.responseXML);

    return wordCoords;
};



/**
 * Disable Fulltext Features
 *
 * @return	void
 */
dlfViewerOl3.prototype.disableFulltextSelect = function() {

    // destroy layer features
    this.map.removeLayer(this.textBlockLayer);
    this.map.removeLayer(this.highlightLayer);
    this.map.removeLayer(this.selectLayer);

    // clear all layers
    this.textBlockLayer.getSource().clear();
    this.highlightLayer.getSource().clear();
    this.selectLayer.getSource().clear();

    $("#tx-dlf-fulltextselection").hide();

};



/**
 * Activate Fulltext Features
 *
 * @param {ol.Feature} feature
 */
dlfViewerOl3.prototype.showFulltext = function(feature) {

    var popupHTML = '<div class="ocrText">' + feature.get('fulltext').replace(/\n/g, '<br />') + '</div>';

    $('#tx-dlf-fulltextselection').html(popupHTML);

};

/**
 * @const
 * @namespace
 */
dlfViewerOl3.style = {};

/**
 * @return {ol.style.Style}
 */
dlfViewerOl3.style.defaultStyle = function() {

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
dlfViewerOl3.style.hoverStyle = function() {

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
dlfViewerOl3.style.selectStyle = function() {

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
dlfViewerOl3.style.wordStyle = function() {

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