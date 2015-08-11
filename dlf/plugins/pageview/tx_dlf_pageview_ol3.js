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
 * @todo check if all interactions / controls are supported
 * @param {Object} settings
 *      {string=} div
 *      {Array.<?>} images
 *      {Array.<?>} fulltexts
 *      {Array.<?>} controls
 * @constructor
 */
var dlfViewerOl3 = function(settings){

    /**
     * The element id of the map container
     * @type {string}
     */
    this.div = dlfUtils.exists(settings.div) ? settings.div : "tx-dlf-map";

    /**
     * Openlayers map object
     * @type {ol.Map|null}
     */
    this.map = null;

    /**
     * Contains image information (e.g. URL, width, height)
     * @type {Array.<string>}
     */
    this.imageUrls = dlfUtils.exists(settings.images) ? settings.images : [];

    /**
     * Contains image information (e.g. URL, width, height)
     * @type {Array.<{src: *, width: *, height: *}>}
     */
    this.images = [];

    /**
     * Fulltext information (e.g. URL)
     * @type {Array.<string|?>}
     */
    this.fulltexts = dlfUtils.exists(settings.fulltexts) ? settings.fulltexts : [];

    /**
     * Original image information (e.g. width, height)
     * @type {Array.<?>}
     */
    this.origImages = [];

    /**
     * ol3 controls which should be added to map
     * @type {Array.<?>}
     */
    this.controls = dlfUtils.exists(settings.controls) ? settings.controls : [];

    /**
     * Offset for the second image
     * @type {number}
     */
    this.offset = 0;

    /**
     * Highlighting layer ol3
     * @type {ol.layer.Vector|null}
     */
    this.highlightLayer = null;

    /**
     * Highlighting fields
     * @type {Array.<?>}
     */
    this.highlightFields = [];

    /**
     * Fulltexts together with the coordinates of the textblocks
     * @type {Array.<?>}
     */
    this.fulltextCoordinates = [];

    /**
     * Lock to check if feature is clicked
     * @type {null|boolean}
     */
    this.featureClicked = null;

    /**
     * Language token
     * @type {string}
     */
    this.lang = dlfUtils.exists(settings.lang) ? settings.lang : 'de';

    this.init();
};

/**
 * Register controls to load for map
 * @todo Check if all controls a initialize properly
 * @param {Array.<string>} control keyswords
 */
dlfViewerOl3.prototype.addControls = function(controls) {

    for (var i in controls) {

        // Initialize control.
        switch(controls[i]) {

            /*             case "OverviewMap":

                controls[i] = new ol.control.OverviewMap();

                break;

           case "PanPanel":

                controls[i] = new OpenLayers.Control.PanPanel();

                break;

            case "PanZoom":

                controls[i] = new OpenLayers.Control.PanZoom();

                break;

            case "PanZoomBar":

                controls[i] = new OpenLayers.Control.PanZoomBar();

                break;

            case "ZoomPanel":

                controls[i] = new OpenLayers.Control.ZoomPanel();

                break;*/

            default:

                controls[i] = null;

        }

        if (!dlfUtils.isNull(controls[i])) {

            // Register control.
            this.controls.push(controls[i]);

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
            view: new ol.View({
                projection: mapProj,
                center: ol.extent.getCenter(mapExtent),
                zoom: 2,
                maxZoom: 8
            })
        });

    }, this);

    // init image loading process
    if (this.imageUrls.length > 0) {

        this.fetchImages(init_);

    };

};


//
//
///**
// * Set Original Image Size
// *
// * @param	array	urls: Array of URLs of the fulltext files
// *
// * @return	void
// */
//dlfViewer.prototype.addFulltexts = function(urls) {
//
//    for (var i in urls) {
//
//        this.fulltexts[i] = urls[i];
//
//    }
//
//};
//
//
///**
// * Get a cookie value
// *
// * @param	string		name: The key of the value
// *
// * @return	string		The key's value
// */
//dlfViewer.prototype.getCookie = function(name) {
//
//    var results = document.cookie.match("(^|;) ?"+name+"=([^;]*)(;|$)");
//
//    if (results) {
//
//        return unescape(results[2]);
//
//    } else {
//
//        return null;
//
//    }
//
//};
//
///**
// * Initialize and display the OpenLayers map with default layers
// *
// * @return	void
// */
//dlfViewer.prototype.init = function() {
//
//    var width = 0;
//
//    var height = 0;
//
//    var layers = [];
//
//    // Create image layers.
//    for (var i in this.images) {
//
//        layers.push(
//            new OpenLayers.Layer.Image(
//                i,
//                this.images[i].src,
//                new OpenLayers.Bounds(this.offset, 0, this.offset + this.images[i].width, this.images[i].height),
//                new OpenLayers.Size(this.images[i].width / 20, this.images[i].height / 20),
//                {
//                    'displayInLayerSwitcher': false,
//                    'isBaseLayer': false,
//                    'maxExtent': new OpenLayers.Bounds(this.offset, 0, this.images.length * (this.offset + this.images[i].width), this.images[i].height),
//                    'visibility': true
//                }
//            )
//        );
//
//        // Set offset for right image in double-page mode.
//        if (this.offset == 0) {
//
//            this.offset = this.images[i].width;
//
//        }
//        // Calculate overall width and height.
//        width += this.images[i].width;
//
//        if (this.images[i].height > height) {
//
//            height = this.images[i].height;
//
//        }
//
//    }
//
//    // Add default controls to controls array.
//    this.controls.unshift(new OpenLayers.Control.Navigation());
//
//    this.controls.unshift(new OpenLayers.Control.Keyboard());
//
//    // Initialize OpenLayers map.
//    this.map = new OpenLayers.Map({
//        'allOverlays': true,
//        'controls': this.controls,
//        'div': this.div,
//        'fractionalZoom': true,
//        'layers': layers,
//        'maxExtent': new OpenLayers.Bounds(0, 0, width, height),
//        'minResolution': 1.0,
//        'numZoomLevels': 20,
//        'units': "m"
//    });
//
//    // Position image according to user preferences.
//    if (this.getCookie("tx-dlf-pageview-centerLon") !== null && this.getCookie("tx-dlf-pageview-centerLat") !== null) {
//
//        this.map.setCenter(
//            [
//                this.getCookie("tx-dlf-pageview-centerLon"),
//                this.getCookie("tx-dlf-pageview-centerLat")
//            ],
//            this.getCookie("tx-dlf-pageview-zoomLevel"),
//            true,
//            true
//        );
//
//    } else {
//
//        this.map.zoomToMaxExtent();
//
//    }
//
//    // add polygon layer if any
//    if (this.highlightFields.length) {
//
//        if (! this.highlightLayer) {
//
//            this.highlightLayer = new OpenLayers.Layer.Vector(
//                "HighLight Words"
//            );
//        }
//
//        for (var i in this.highlightFields) {
//
//            if (this.origImages[0].scale == 0) {
//
//                // scale may be still zero in this context
//                this.origImages[0] = {
//
//                    'scale': this.images[0].width/this.origImages[0].width,
//
//                };
//
//            }
//
//            var polygon = this.createPolygon(0, this.highlightFields[i][0], this.highlightFields[i][1], this.highlightFields[i][2], this.highlightFields[i][3]);
//
//            this.addPolygonlayer(this.highlightLayer, polygon, 'String');
//
//        }
//
//        this.map.addLayer(this.highlightLayer);
//
//    }
//
//    // keep fulltext feature active
//    var isFulltextActive = this.getCookie("tx-dlf-pageview-fulltext-select");
//
//    if (isFulltextActive == 'enabled') {
//
//        this.enableFulltextSelect();
//
//    }
//
//    //~ this.map.addControl(new OpenLayers.Control.MousePosition());
//    //~ this.map.addControl(new OpenLayers.Control.LayerSwitcher());
//};
//
///**
// * Show Popup with OCR results
// *
// * @param {Object} text
// */
//dlfViewer.prototype.showPopupDiv = function(text) {
//
//    var popupHTML = '<div class="ocrText">' + text.replace(/\n/g, '<br />') + '</div>';
//
//    $('#tx-dlf-fulltextselection').html(popupHTML);
//
//};
//
///**
// * Destroy boxLayer if popup closed
// */
//dlfViewer.prototype.popUpClosed = function() {
//
//    this.hide();
//};
//
///**
// * Save current user preferences in cookie
// *
// * @return	void
// */
//dlfViewer.prototype.saveSettings = function() {
//
//    if (this.map !== null) {
//
//        this.setCookie("tx-dlf-pageview-zoomLevel", this.map.getZoom());
//
//        this.setCookie("tx-dlf-pageview-centerLon", this.map.getCenter().lon);
//
//        this.setCookie("tx-dlf-pageview-centerLat", this.map.getCenter().lat);
//
//    }
//
//};
//
///**
// * Set a cookie value
// *
// * @param	string		name: The key of the value
// * @param	mixed		value: The value to save
// *
// * @return	void
// */
//dlfViewer.prototype.setCookie = function(name, value) {
//
//    document.cookie = name+"="+escape(value)+"; path=/";
//
//};
//
///**
// * Set OpenLayers' div
// *
// * @param	string		elementId: The div element's @id attribute value
// *
// * @return	void
// */
//dlfViewer.prototype.setDiv = function(elementId) {
//
//    // Check if element exists.
//    if ($("#"+elementId).length) {
//
//        this.div = elementId;
//
//    }
//
//};
//
///**
// * Set OpenLayers' language
// *
// * @param	string		lang: The language code
// *
// * @return	void
// */
//dlfViewer.prototype.setLang = function(lang) {
//
//    OpenLayers.Lang.setCode(lang);
//
//};
//
//// Register page unload handler to save user settings.
//$(window).unload(function() {
//
//    tx_dlf_viewer.saveSettings();
//
//});
//
///**
// * Add highlight field
// *
// * @param	integer x1
// * @param	integer y1
// * @param	integer x2
// * @param	integer y2
// *
// * @return	void
// */
//dlfViewer.prototype.addHighlightField = function(x1, y1, x2, y2) {
//
//    this.highlightFields.push([x1,y1,x2,y2]);
//
//};
//
///**
// * Add layer with highlighted words found
// *
// * @param	integer x1
// * @param	integer y1
// * @param	integer x2
// * @param	integer y2
// *
// * @return	void
//
// */
//dlfViewer.prototype.createPolygon = function(image, x1, y1, x2, y2) {
//
//    if (this.origImages.length > 1 && image == 1) {
//
//        var scale = this.origImages[1].scale;
//        var height = this.images[1].height;
//        var offset = this.images[0].width;
//
//    } else {
//
//        var scale = this.origImages[0].scale;
//        var height = this.images[0].height;
//        var offset = 0;
//
//    }
//
//    //alert('image ' + image + ' scale: ' + scale + ' height: ' + height + ' offset: ' + offset);
//
//    var polygon = new OpenLayers.Geometry.Polygon (
//        new OpenLayers.Geometry.LinearRing (
//            [
//                new OpenLayers.Geometry.Point(offset + (scale * x1), height - (scale *y1)),
//                new OpenLayers.Geometry.Point(offset + (scale * x2), height - (scale *y1)),
//                new OpenLayers.Geometry.Point(offset + (scale * x2), height - (scale *y2)),
//                new OpenLayers.Geometry.Point(offset + (scale * x1), height - (scale *y2)),
//            ]
//        )
//    );
//
//    var feature = new OpenLayers.Feature.Vector(polygon);
//
//    return feature;
//
//};
///**
// * Add layer with highlighted polygon
// *
// * http://dev.openlayers.org/docs/files/OpenLayers/Symbolizer/Polygon-js.html
// *
// * @param	{Object} layer
// * @param	{Object} feature
// * @param	integer type
// *
// * @return	void
//
// */
//dlfViewer.prototype.addPolygonlayer = function(layer, feature, type) {
//
//    if (layer instanceof OpenLayers.Layer.Vector) {
//
//        switch (type) {
//            case 'TextBlock': var highlightStyle = new OpenLayers.Style({
//                strokeColor : '#cccccc',
//                strokeOpacity : 0.8,
//                strokeWidth : 3,
//                fillColor : '#aa0000',
//                fillOpacity : 0.1,
//                cursor : 'inherit'
//            });
//                break;
//            case 'String': var highlightStyle = new OpenLayers.Style({
//                strokeColor : '#ee9900',
//                strokeOpacity : 0.8,
//                strokeWidth : 1,
//                fillColor : '#ee9900',
//                fillOpacity : 0.2,
//                cursor : 'inherit'
//            });
//                break;
//            case 3: var highlightStyle = new OpenLayers.Style({
//                strokeColor : '#ffffff',
//                strokeOpacity : 0.8,
//                strokeWidth : 4,
//                fillColor : '#3d4ac2',
//                fillOpacity : 0.5,
//                cursor : 'inherit'
//            });
//                break;
//            default: var highlightStyle = new OpenLayers.Style({
//                strokeColor : '#ee9900',
//                strokeOpacity : 0.8,
//                strokeWidth : 1,
//                fillColor : '#ee9900',
//                fillOpacity : 0.4,
//                cursor : 'inherit'
//            });
//        };
//
//        var hoverStyle = new OpenLayers.Style({
//            strokeColor : '#cccccc',
//            strokeOpacity : 0.8,
//            strokeWidth : 1,
//            fillColor : '#ee9900',
//            fillOpacity : 0.2,
//            cursor : 'inherit'
//        });
//
//        var selectStyle = new OpenLayers.Style({
//            strokeColor : '#aa0000',
//            strokeOpacity : 0.8,
//            strokeWidth : 1,
//            fillColor : '#ee9900',
//            fillOpacity : 0.2,
//            cursor : 'inherit'
//        });
//
//        var stylemapObj = new OpenLayers.StyleMap(
//            {
//                'default' : highlightStyle,
//                'hover' : hoverStyle,
//                'select' : selectStyle,
//            }
//        );
//
//        layer.styleMap = stylemapObj;
//
//        layer.addFeatures([feature]);
//
//    }
//
//};
//
//
///**
// * Set Original Image Size
// *
// * @param	integer image number
// * @param	integer width
// * @param	integer height
// *
// * @return	void
// */
//dlfViewer.prototype.setOrigImage = function(i, width, height) {
//
//    if (width && height) {
//
//        this.origImages[i] = {
//            'width': width,
//            'height': height,
//            'scale': tx_dlf_viewer.images[i].width/width,
//        };
//
//    }
//
//};
//
//
///**
// * Read ALTO file and return found words
// *
// * @param {Object} url
// */
//dlfViewer.prototype.loadALTO = function(url){
//
//    var request = OpenLayers.Request.GET({
//        url: url,
//        async: false
//    });
//
//    var format = new OpenLayers.Format.ALTO();
//
//    if (request.responseXML)
//        var wordCoords = format.read(request.responseXML);
//
//    return wordCoords;
//};
//
///**
// * Activate Fulltext Features
// *
// * @return	void
// */
//dlfViewer.prototype.toggleFulltextSelect = function() {
//
//    var isFulltextActive = this.getCookie("tx-dlf-pageview-fulltext-select");
//
//    if (isFulltextActive == 'enabled') {
//
//        this.disableFulltextSelect();
//        this.setCookie("tx-dlf-pageview-fulltext-select", 'disabled');
//
//    } else {
//
//        this.enableFulltextSelect();
//        this.setCookie("tx-dlf-pageview-fulltext-select", 'enabled');
//
//    }
//
//};
//
///**
// * Disable Fulltext Features
// *
// * @return	void
// */
//dlfViewer.prototype.disableFulltextSelect = function() {
//
//    // destroy layer features
//    this.textBlockLayer.destroyFeatures();
//    $("#tx-dlf-fulltextselection").hide();
//
//};
//
///**
// * Activate Fulltext Features
// *
// * @return	void
// */
//dlfViewer.prototype.enableFulltextSelect = function() {
//
//    // Create image layers.
//    for (var i in this.images) {
//
//        if (this.fulltexts[i]) {
//
//            this.fullTextCoordinates[i] = this.loadALTO(this.fulltexts[i]);
//
//        }
//
//    }
//
//    // add fulltext layers if we have fulltexts to show
//    if (this.fullTextCoordinates.length > 0) {
//
//        for (var i in this.images) {
//
//            var textBlockCoordinates = this.fullTextCoordinates[i];
//
//            for (var j in textBlockCoordinates) {
//
//                // set scale either by Page or Printspace
//                if (textBlockCoordinates[j].type == 'Page') {
//
//                    if (! tx_dlf_viewer.origImages[i]) {
//                        this.setOrigImage(i, textBlockCoordinates[j].geometry['width'] , textBlockCoordinates[j].geometry['height'] );
//                    }
//
//                } else if (textBlockCoordinates[j].type == 'PrintSpace') {
//
//                    if (! tx_dlf_viewer.origImages[i]) {
//                        this.setOrigImage(i, textBlockCoordinates[j].geometry['width'], textBlockCoordinates[j].geometry['height']);
//                    }
//                }
//                else if (textBlockCoordinates[j].type == 'TextBlock') {
//
//                    if (! this.textBlockLayer) {
//
//                        this.textBlockLayer = new OpenLayers.Layer.Vector(
//
//                            "TextBlock"
//
//                        );
//
//                        this.textBlockLayer.events.on({
//
//                            'featureover': function(e) {
//
//                                if (e.feature != this.featureClicked) {
//                                    e.feature.layer.drawFeature(e.feature, "hover");
//                                }
//
//                            },
//
//                            'featureout': function(e) {
//
//                                if (e.feature != this.featureClicked) {
//                                    e.feature.layer.drawFeature(e.feature, "default");
//                                }
//
//                            },
//
//                            "featureclick": function(e) {
//
//                                if (this.featureClicked != null) {
//
//                                    this.featureClicked.layer.drawFeature(this.featureClicked, "default");
//
//                                }
//
//                                this.showFulltext(e);
//                                e.feature.layer.drawFeature(e.feature, "select");
//                                this.featureClicked = e.feature;
//
//                            },
//
//                            scope: this
//
//                        });
//
//                    }
//
//                    var polygon = this.createPolygon(i, textBlockCoordinates[j].coords['x1'], textBlockCoordinates[j].coords['y1'], textBlockCoordinates[j].coords['x2'], textBlockCoordinates[j].coords['y2']);
//
//                    this.addPolygonlayer(this.textBlockLayer, polygon, 'TextBlock');
//
//                }
//
//            }
//
//        }
//
//        if (this.textBlockLayer instanceof OpenLayers.Layer.Vector) {
//
//            tx_dlf_viewer.map.addLayer(this.textBlockLayer);
//            $("#tx-dlf-fulltextselection").show();
//
//        }
//
//    }
//
//};
//
///**
// * Activate Fulltext Features
// *
// * @return	void
// */
//dlfViewer.prototype.showFulltext = function(evt) {
//
//    var feature = evt.feature;
//
//    var bounds = feature.geometry.getBounds();
//
//    var img = 0;
//
//    // selected TextBlock in left or right image?
//    if (bounds.left > tx_dlf_viewer.offset) {
//
//        img = 1;
//
//    }
//
//    var scale = tx_dlf_viewer.origImages[img].scale;
//
//    var text = '';
//
//    var wordCoord = tx_dlf_viewer.fullTextCoordinates[img];
//
//    if (wordCoord.length > 0) {
//
//        var size_disp = new OpenLayers.Size(tx_dlf_viewer.images[img].width, tx_dlf_viewer.images[img].height);
//
//        // walk through all textblocks
//        for (var i = 0; i < wordCoord.length; i++) {
//
//            if (wordCoord[i].type == 'TextBlock') {
//
//                // find center point of word coordinates
//                var centerWord = new OpenLayers.Geometry.Point(
//                    (img * this.offset) + (scale * (wordCoord[i].coords['x1'] + ((wordCoord[i].coords['x2'] - wordCoord[i].coords['x1']) / 2))),
//                    (size_disp.h - scale * (wordCoord[i].coords['y1'] + (wordCoord[i].coords['y2'] - wordCoord[i].coords['y1']) / 2))
//                );
//
//                // take word if center point is inside the drawn box
//                if (feature.geometry.containsPoint(centerWord)) {
//                    //~ var polygon = tx_dlf_viewer.createPolygon(img, wordCoord[i].coords['x1'] - (tx_dlf_viewer.offset * img)/tx_dlf_viewer.origImages[img].scale, wordCoord[i].coords['y1'], wordCoord[i].coords['x2'] - (tx_dlf_viewer.offset * img)/tx_dlf_viewer.origImages[img].scale, wordCoord[i].coords['y2']);
//                    //~ tx_dlf_viewer.addPolygonlayer(tx_dlf_viewer.textBlockLayer, polygon, 3);
//
//                    text += wordCoord[i].fulltext + " ";
//                }
//            }
//        }
//    }
//
//    tx_dlf_viewer.showPopupDiv(text);
//
//};