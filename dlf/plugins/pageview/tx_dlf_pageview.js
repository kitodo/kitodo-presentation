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
 * Constructor for dlfViewer
 *
 * @return	void
 */
function dlfViewer() {

	/**
	 * This holds the element's @ID the OpenLayers map is rendered into
	 *
	 * var string
	 */
	this.div = "tx-dlf-map";

	/**
	 * This holds the OpenLayers map object
	 *
	 * var OpenLayers.Map
	 */
	this.map = null;

	/**
	 * This holds the images' information like URL, width and height
	 *
	 * var array
	 */
	this.images = [];

	/**
	 * This holds the fulltexts' information like URL
	 *
	 * var array
	 */
	this.fulltexts = [];

	/**
	 * This holds the original images' information like width and height
	 *
	 * var array
	 */
	this.origImages = [];

	/**
	 * This holds information about the loading state of the images
	 *
	 * var array
	 */
	this.imagesLoaded = [0, 0];

	/**
	 * This holds the controls for the OpenLayers map
	 *
	 * var array
	 */
	this.controls = [];

	/**
	 * This holds the offset for the second image
	 *
	 * var integer
	 */
	this.offset = 0;

	/**
	 * This holds the highlightning layer
	 *
	 * var OpenLayers.Layer.Vector
	 */
	this.highlightLayer = null;

	/**
	 * This holds the highlightning layer
	 *
	 * var array
	 */
	this.highlightFields = [];

	/**
	 * This holds all fulltexts and coordinates of the textblocks
	 *
	 * var array
	 */
	this.fullTextCoordinates = [];

	this.featureClicked = null;

	this.croppingEnabled = false;

	this.selectionInteraction = null;

	this.source = null;

	this.view = null;

	this.ov_view = null;

	this.cropFeature = null;

	this.magnifierEnabled = false;

}

/**
 * Register controls to load for map
 *
 * @param	array		controls: Array of control keywords
 *
 * @return	void
 */
dlfViewer.prototype.addControls = function(controls) {

	for (var i in controls) {

		// Initialize control.
		switch(controls[i]) {

			case "OverviewMap":

				controls[i] = new ol.control.OverviewMap();

				break;

			case "PanPanel":

				controls[i] = new ol.control.Rotate();

				break;

			case "PanZoom":

				controls[i] = new ol.control.ZoomSlider();

				break;

			case "PanZoomBar":

				controls[i] = new ol.control.FullScreen();

				break;

			case "ZoomPanel":

				controls[i] = new ol.control.Zoom();

				break;

			default:

				controls[i] = null;

		}

		if (controls[i] !== null) {

			// Register control.
			this.controls.push(controls[i]);

		}

	}

};

/**
 * Register image files to load into map
 *
 * @param	array		urls: Array of URLs of the image files
 *
 * @return	void
 */
dlfViewer.prototype.addImages = function(urls) {

	var img = [];

	// Get total number of images.
	this.imagesLoaded[1] = urls.length;

	for (var i in urls) {

		// Prepare image loading.
		this.images[i] = {
			'src': urls[i],
			'width': 0,
			'height': 0
		};

		// Create new Image object.
		img[i] = new Image();

		// Register onload handler.
		img[i].onload = function() {

			for (var j in tx_dlf_viewer.images) {

				if (tx_dlf_viewer.images[j].src == this.src) {

					// Add additional image data.
					tx_dlf_viewer.images[j] = {
						'src': this.src,
						'width': this.width,
						'height': this.height
					};

					break;

				}

			}

			// Count image as completely loaded.
			tx_dlf_viewer.imagesLoaded[0]++;

			// Initialize OpenLayers map if all images are completely loaded.
			if (tx_dlf_viewer.imagesLoaded[0] == tx_dlf_viewer.imagesLoaded[1]) {

				tx_dlf_viewer.init();

			}

		};

		// Initialize image loading.
		img[i].src = urls[i];

	}

};


/**
 * Set Original Image Size
 *
 * @param	array	urls: Array of URLs of the fulltext files
 *
 * @return	void
 */
dlfViewer.prototype.addFulltexts = function(urls) {

	for (var i in urls) {

		this.fulltexts[i] = urls[i];

	}

};


/**
 * Get a cookie value
 *
 * @param	string		name: The key of the value
 *
 * @return	string		The key's value
 */
dlfViewer.prototype.getCookie = function(name) {

	var results = document.cookie.match("(^|;) ?"+name+"=([^;]*)(;|$)");

	if (results) {

		return unescape(results[2]);

	} else {

		return null;

	}

};

/**
 * Initialize and display the OpenLayers map with default layers
 *
 * @return	void
 */
dlfViewer.prototype.init = function() {

	var width = 0;

	var height = 0;

	var layers = [];



	// Create image layers.
	for (var i in this.images) {
		
		// var extent = [this.offset, 0, this.offset + this.images[i].width, this.images[i].height];
		var extent = [0, 0, this.offset + this.images[i].width, this.images[i].height];

		// var extent = [0, 0, 1, 1];

		var projection = new ol.proj.Projection({
	      code: 'xkcd-image',
	      units: 'pixels',
	      extent: extent
	    });

	    this.imageLayer = new ol.layer.Image({
	    	source: new ol.source.ImageStatic({
	    		attributions: [
	          		new ol.Attribution({
	            		html: 'Test'
	          		})
	        	],
	        	url: this.images[i].src,
	        	projection: projection,
	        	imageExtent: extent
	      		}),
	    	extent: extent
    	})

		layers.push(this.imageLayer
			
			// new ol.layer.Image(
			// 	i,
			// 	this.images[i].src,
			// 	new ol.Bounds(this.offset, 0, this.offset + this.images[i].width, this.images[i].height),
			// 	new ol.Size(this.images[i].width / 20, this.images[i].height / 20),
			// 	{
			// 		'displayInLayerSwitcher': false,
			// 		'isBaseLayer': false,
			// 		'maxExtent': new ol.Bounds(this.offset, 0, this.images.length * (this.offset + this.images[i].width), this.images[i].height),
			// 		'visibility': true
			// 	}
			// )
		);

		// Set offset for right image in double-page mode.
		if (this.offset == 0) {

			this.offset = this.images[i].width;

		}
		// Calculate overall width and height.
		width += this.images[i].width;

		if (this.images[i].height > height) {

			height = this.images[i].height;

		}

	}

	// var mousePositionControl = new ol.control.MousePosition({
 //        // coordinateFormat: ol.coordinate.createStringXY(4),
 //        // projection: 'EPSG:4326',
 //        // // comment the following two lines to have the mouse position
 //        // // be placed within the map.
 //        className: 'custom-mouse-position',
 //        target: document.getElementById('mouse-position'),
 //        change: function(){
 //        	console.log("DAS IST EIN TEST");
 //        	console.log(this);
 //        }
 //        // undefinedHTML: '&nbsp;'
 //      });
	

	this.source = new ol.source.Vector();
	// crop selection style
    this.selectionInteraction = new ol.interaction.DragBox({
        condition: ol.events.condition.noModifierKeys,
        style: new ol.style.Style({
            stroke: new ol.style.Stroke({
                color: [0, 0, 255, 1]
            })
        })
    });

	// Add default controls to controls array.
	// this.controls.unshift(new ol.Control.Navigation());

	// this.controls.unshift(new ol.Control.Keyboard());
	

	this.view = new ol.View({
        projection: projection,
        center: ol.extent.getCenter(extent),
        zoom: 2,
        maxZoom: 10,
        constrainRotation: 4,
        extent: ol.proj.transform(extent, 
        	'EPSG:4326', 'EPSG:3857'
        )
      });

	// Initialize OpenLayers map.
	this.map = new ol.Map({
		controls: this.controls,
     	layers: layers,
      	target: 'tx-dlf-map',
      	view: this.view,
      	interactions: ol.interaction.defaults().extend([new Drag()]),
    });
	// this.map = new OpenLayers.Map({
	// 	'allOverlays': true,
	// 	'controls': this.controls,
	// 	'div': this.div,
	// 	'fractionalZoom': true,
	// 	'layers': layers,
	// 	'maxExtent': new OpenLayers.Bounds(0, 0, width, height),
	// 	'minResolution': 1.0,
	// 	'numZoomLevels': 20,
	// 	'units': "m"
	// });
	
	// cropping function
	if (this.croppingEnabled) {
		// a DragBox interaction used to select features by drawing boxes
	    var dragBox = new ol.interaction.DragBox({
	    	condition: ol.events.condition.platformModifierKeyOnly
	    });

	    this.map.addInteraction(dragBox);

	    var dlfViewer = this;
	    dragBox.on('boxend', function() {
	        // features that intersect the box are added to the collection of
	        // selected features, and their names are displayed in the "info"
	        // div
	        var info = [];
	        var extent = dragBox.getGeometry().getExtent();
	        var imageExtent = dlfViewer.map.getLayers().item(0).getExtent();

	        var pixel = ol.extent.getIntersection(imageExtent, extent);
	        var rotation = dlfViewer.map.getView().getRotation();

	        // fill form with coordinates
	        $('#addToBasketForm #startX').val(Math.round(pixel[0]));
	        $('#addToBasketForm #startY').val(Math.round(pixel[1]));
	        $('#addToBasketForm #endX').val(Math.round(pixel[2]));
	        $('#addToBasketForm #endY').val(Math.round(pixel[3]));
	        $('#addToBasketForm #rotation').val(Math.round(dlfViewer.radianToDegree(rotation)));

	    });
	} 
	

	// Position image according to user preferences.
	if (this.getCookie("tx-dlf-pageview-centerLon") !== null && this.getCookie("tx-dlf-pageview-centerLat") !== null) {

		this.map.getView().setCenter(
			[
				this.getCookie("tx-dlf-pageview-centerLon"),
				this.getCookie("tx-dlf-pageview-centerLat")
			]
		);
		this.map.getView().setZoom(this.getCookie("tx-dlf-pageview-zoomLevel"));
		// this.map.setCenter(
		// 	[
		// 		this.getCookie("tx-dlf-pageview-centerLon"),
		// 		this.getCookie("tx-dlf-pageview-centerLat")
		// 	],
		// 	this.getCookie("tx-dlf-pageview-zoomLevel"),
		// 	true,
		// 	true
		// );

	} else {

		// this.map.zoomToMaxExtent();
		// var extent = source.getExtent();
		this.map.getView().fit(extent, this.map.getSize()); 

	}

	// add polygon layer if any
	if (this.highlightFields.length) {

		if (! this.highlightLayer) {

			this.highlightLayer = new OpenLayers.Layer.Vector(
									"HighLight Words"
								);
		}

		for (var i in this.highlightFields) {

			if (this.origImages[0].scale == 0) {

				// scale may be still zero in this context
				this.origImages[0] = {

					'scale': this.images[0].width/this.origImages[0].width,

				};

			}

			var polygon = this.createPolygon(0, this.highlightFields[i][0], this.highlightFields[i][1], this.highlightFields[i][2], this.highlightFields[i][3]);

			this.addPolygonlayer(this.highlightLayer, polygon, 'String');

		}

		this.map.addLayer(this.highlightLayer);

	}

	// keep fulltext feature active
	var isFulltextActive = this.getCookie("tx-dlf-pageview-fulltext-select");

	if (isFulltextActive == 'enabled') {

		this.enableFulltextSelect();

	}

	//~ this.map.addControl(new OpenLayers.Control.MousePosition());
	//~ this.map.addControl(new OpenLayers.Control.LayerSwitcher());
};

/**
 * convert radian to degree
 */
dlfViewer.prototype.radianToDegree = function(radian) {
    return radian * (180 / Math.PI);
}

/**
 * Show Popup with OCR results
 *
 * @param {Object} text
 */
dlfViewer.prototype.showPopupDiv = function(text) {

	var popupHTML = '<div class="ocrText">' + text.replace(/\n/g, '<br />') + '</div>';

	$('#tx-dlf-fulltextselection').html(popupHTML);

};

/**
 * Destroy boxLayer if popup closed
 */
dlfViewer.prototype.popUpClosed = function() {

	this.hide();
};

/**
 * Save current user preferences in cookie
 *
 * @return	void
 */
dlfViewer.prototype.saveSettings = function() {

	if (this.map !== null) {

		this.setCookie("tx-dlf-pageview-zoomLevel", this.map.getZoom());

		this.setCookie("tx-dlf-pageview-centerLon", this.map.getCenter().lon);

		this.setCookie("tx-dlf-pageview-centerLat", this.map.getCenter().lat);

	}

};

/**
 * Set a cookie value
 *
 * @param	string		name: The key of the value
 * @param	mixed		value: The value to save
 *
 * @return	void
 */
dlfViewer.prototype.setCookie = function(name, value) {

	document.cookie = name+"="+escape(value)+"; path=/";

};

/**
 * Set OpenLayers' div
 *
 * @param	string		elementId: The div element's @id attribute value
 *
 * @return	void
 */
dlfViewer.prototype.setDiv = function(elementId) {

	// Check if element exists.
	if ($("#"+elementId).length) {

		this.div = elementId;

	}

};

/**
 * Set OpenLayers' language
 *
 * @param	string		lang: The language code
 *
 * @return	void
 */
dlfViewer.prototype.setLang = function(lang) {

	// ol.Lang.setCode(lang);

};

// Register page unload handler to save user settings.
$(window).unload(function() {

	tx_dlf_viewer.saveSettings();

});

/**
 * Add highlight field
 *
 * @param	integer x1
 * @param	integer y1
 * @param	integer x2
 * @param	integer y2
 *
 * @return	void
 */
dlfViewer.prototype.addHighlightField = function(x1, y1, x2, y2) {

	this.highlightFields.push([x1,y1,x2,y2]);

};

/**
 * Add layer with highlighted words found
 *
 * @param	integer x1
 * @param	integer y1
 * @param	integer x2
 * @param	integer y2
 *
 * @return	void

 */
dlfViewer.prototype.createPolygon = function(image, x1, y1, x2, y2) {

	if (this.origImages.length > 1 && image == 1) {

		var scale = this.origImages[1].scale;
		var height = this.images[1].height;
		var offset = this.images[0].width;

	} else {

		var scale = this.origImages[0].scale;
		var height = this.images[0].height;
		var offset = 0;

	}

	//alert('image ' + image + ' scale: ' + scale + ' height: ' + height + ' offset: ' + offset);

	var polygon = new OpenLayers.Geometry.Polygon (
		new OpenLayers.Geometry.LinearRing (
			[
			new OpenLayers.Geometry.Point(offset + (scale * x1), height - (scale *y1)),
			new OpenLayers.Geometry.Point(offset + (scale * x2), height - (scale *y1)),
			new OpenLayers.Geometry.Point(offset + (scale * x2), height - (scale *y2)),
			new OpenLayers.Geometry.Point(offset + (scale * x1), height - (scale *y2)),
			]
		)
	);

	var feature = new OpenLayers.Feature.Vector(polygon);

	return feature;

};
/**
 * Add layer with highlighted polygon
 *
 * http://dev.openlayers.org/docs/files/OpenLayers/Symbolizer/Polygon-js.html
 *
 * @param	{Object} layer
 * @param	{Object} feature
 * @param	integer type
 *
 * @return	void

 */
dlfViewer.prototype.addPolygonlayer = function(layer, feature, type) {

	if (layer instanceof OpenLayers.Layer.Vector) {

		switch (type) {
			case 'TextBlock': var highlightStyle = new OpenLayers.Style({
					strokeColor : '#cccccc',
					strokeOpacity : 0.8,
					strokeWidth : 3,
					fillColor : '#aa0000',
					fillOpacity : 0.1,
					cursor : 'inherit'
				});
				break;
			case 'String': var highlightStyle = new OpenLayers.Style({
					strokeColor : '#ee9900',
					strokeOpacity : 0.8,
					strokeWidth : 1,
					fillColor : '#ee9900',
					fillOpacity : 0.2,
					cursor : 'inherit'
				});
				break;
			case 3: var highlightStyle = new OpenLayers.Style({
					strokeColor : '#ffffff',
					strokeOpacity : 0.8,
					strokeWidth : 4,
					fillColor : '#3d4ac2',
					fillOpacity : 0.5,
					cursor : 'inherit'
				});
				break;
			default: var highlightStyle = new OpenLayers.Style({
					strokeColor : '#ee9900',
					strokeOpacity : 0.8,
					strokeWidth : 1,
					fillColor : '#ee9900',
					fillOpacity : 0.4,
					cursor : 'inherit'
				});
		}

		var hoverStyle = new OpenLayers.Style({
			strokeColor : '#cccccc',
			strokeOpacity : 0.8,
			strokeWidth : 1,
			fillColor : '#ee9900',
			fillOpacity : 0.2,
			cursor : 'inherit'
		});

		var selectStyle = new OpenLayers.Style({
			strokeColor : '#aa0000',
			strokeOpacity : 0.8,
			strokeWidth : 1,
			fillColor : '#ee9900',
			fillOpacity : 0.2,
			cursor : 'inherit'
		});

		var stylemapObj = new OpenLayers.StyleMap(
			{
				'default' : highlightStyle,
				'hover' : hoverStyle,
				'select' : selectStyle,
			}
		);

		layer.styleMap = stylemapObj;

		layer.addFeatures([feature]);

	}

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
dlfViewer.prototype.setOrigImage = function(i, width, height) {

	if (width && height) {

		this.origImages[i] = {
			'width': width,
			'height': height,
			'scale': tx_dlf_viewer.images[i].width/width,
		};

	}

};


/**
 * Read ALTO file and return found words
 *
 * @param {Object} url
 */
dlfViewer.prototype.loadALTO = function(url){

    var request = OpenLayers.Request.GET({
        url: url,
        async: false
    });

    var format = new OpenLayers.Format.ALTO();

    if (request.responseXML)
        var wordCoords = format.read(request.responseXML);

    return wordCoords;
};

/**
 * Activate Fulltext Features
 *
 * @return	void
 */
dlfViewer.prototype.toggleFulltextSelect = function() {

	var isFulltextActive = this.getCookie("tx-dlf-pageview-fulltext-select");

	if (isFulltextActive == 'enabled') {

		this.disableFulltextSelect();
		this.setCookie("tx-dlf-pageview-fulltext-select", 'disabled');

	} else {

		this.enableFulltextSelect();
		this.setCookie("tx-dlf-pageview-fulltext-select", 'enabled');

	}

};

/**
 * Disable Fulltext Features
 *
 * @return	void
 */
dlfViewer.prototype.disableFulltextSelect = function() {

	// destroy layer features
	this.textBlockLayer.destroyFeatures();
	$("#tx-dlf-fulltextselection").hide();

};

/**
 * Activate Fulltext Features
 *
 * @return	void
 */
dlfViewer.prototype.enableFulltextSelect = function() {

	// Create image layers.
	for (var i in this.images) {

		if (this.fulltexts[i]) {

			this.fullTextCoordinates[i] = this.loadALTO(this.fulltexts[i]);

		}

	}

	// add fulltext layers if we have fulltexts to show
	if (this.fullTextCoordinates.length > 0) {

		for (var i in this.images) {

			var textBlockCoordinates = this.fullTextCoordinates[i];

			for (var j in textBlockCoordinates) {

				// set scale either by Page or Printspace
				if (textBlockCoordinates[j].type == 'Page') {

					if (! tx_dlf_viewer.origImages[i]) {
						this.setOrigImage(i, textBlockCoordinates[j].geometry['width'] , textBlockCoordinates[j].geometry['height'] );
					}

				} else if (textBlockCoordinates[j].type == 'PrintSpace') {

					if (! tx_dlf_viewer.origImages[i]) {
						this.setOrigImage(i, textBlockCoordinates[j].geometry['width'], textBlockCoordinates[j].geometry['height']);
					}
				}
				else if (textBlockCoordinates[j].type == 'TextBlock') {

					if (! this.textBlockLayer) {

						this.textBlockLayer = new OpenLayers.Layer.Vector(

							"TextBlock"

						);

						this.textBlockLayer.events.on({

							'featureover': function(e) {

								if (e.feature != this.featureClicked) {
									e.feature.layer.drawFeature(e.feature, "hover");
								}

							},

							'featureout': function(e) {

								if (e.feature != this.featureClicked) {
									e.feature.layer.drawFeature(e.feature, "default");
								}

							},

							"featureclick": function(e) {

								if (this.featureClicked != null) {

									this.featureClicked.layer.drawFeature(this.featureClicked, "default");

								}

								this.showFulltext(e);
								e.feature.layer.drawFeature(e.feature, "select");
								this.featureClicked = e.feature;

							},

							scope: this

						});

					}

					var polygon = this.createPolygon(i, textBlockCoordinates[j].coords['x1'], textBlockCoordinates[j].coords['y1'], textBlockCoordinates[j].coords['x2'], textBlockCoordinates[j].coords['y2']);

					this.addPolygonlayer(this.textBlockLayer, polygon, 'TextBlock');

				}

			}

		}

		if (this.textBlockLayer instanceof OpenLayers.Layer.Vector) {

			tx_dlf_viewer.map.addLayer(this.textBlockLayer);
			$("#tx-dlf-fulltextselection").show();

		}

	}

};

/**
 * Activate Fulltext Features
 *
 * @return	void
 */
dlfViewer.prototype.showFulltext = function(evt) {

	var feature = evt.feature;

	var bounds = feature.geometry.getBounds();

	var img = 0;

	// selected TextBlock in left or right image?
	if (bounds.left > tx_dlf_viewer.offset) {

		img = 1;

	}

	var scale = tx_dlf_viewer.origImages[img].scale;

    var text = '';

    var wordCoord = tx_dlf_viewer.fullTextCoordinates[img];

	if (wordCoord.length > 0) {

		var size_disp = new OpenLayers.Size(tx_dlf_viewer.images[img].width, tx_dlf_viewer.images[img].height);

		// walk through all textblocks
		for (var i = 0; i < wordCoord.length; i++) {

			if (wordCoord[i].type == 'TextBlock') {

				// find center point of word coordinates
				var centerWord = new OpenLayers.Geometry.Point(
					(img * this.offset) + (scale * (wordCoord[i].coords['x1'] + ((wordCoord[i].coords['x2'] - wordCoord[i].coords['x1']) / 2))),
					(size_disp.h - scale * (wordCoord[i].coords['y1'] + (wordCoord[i].coords['y2'] - wordCoord[i].coords['y1']) / 2))
				);

				// take word if center point is inside the drawn box
				if (feature.geometry.containsPoint(centerWord)) {
					//~ var polygon = tx_dlf_viewer.createPolygon(img, wordCoord[i].coords['x1'] - (tx_dlf_viewer.offset * img)/tx_dlf_viewer.origImages[img].scale, wordCoord[i].coords['y1'], wordCoord[i].coords['x2'] - (tx_dlf_viewer.offset * img)/tx_dlf_viewer.origImages[img].scale, wordCoord[i].coords['y2']);
					//~ tx_dlf_viewer.addPolygonlayer(tx_dlf_viewer.textBlockLayer, polygon, 3);

					text += wordCoord[i].fulltext + " ";
				}
			}
		}
	}

	tx_dlf_viewer.showPopupDiv(text);

};

/**
 * enables cropping
 */
dlfViewer.prototype.enableCropping = function () {
	this.croppingEnabled = true;
}

dlfViewer.prototype.degreeToRadian = function (degree) {
	return degree * (Math.PI / 180);
}

dlfViewer.prototype.radianToDegree = function (radian) {
	return radian * (180 / Math.PI);
}

dlfViewer.prototype.rotate = function (direction) {
	if (direction == 'left') {
		var rotation = this.radianToDegree(this.view.getRotation());
        newRotation = this.degreeToRadian(rotation - 90);
        this.view.rotate(newRotation);
        if (this.ov_view != null) {
        	this.ov_view.rotate(newRotation);
        }
	} else {
		var rotation = this.radianToDegree(this.view.getRotation());
        newRotation = this.degreeToRadian(rotation + 90);
        this.view.rotate(newRotation);
        if (this.ov_view != null) {
        	this.ov_view.rotate(newRotation);
        }
	}
	
}

dlfViewer.prototype.activateSelection = function () {
	var viewerObject = this;
	// remove all features
	viewerObject.resetCropSelection();

    this.selectionInteraction.on('boxend', function (evt) {
        var geom = evt.target.getGeometry();
        this.cropFeature = new ol.Feature({
            geometry: geom
        });
        viewerObject.source.addFeature(this.cropFeature);

        // add to basket button
        var extent = evt.target.getGeometry().getExtent();
        var imageExtent = viewerObject.map.getLayers().item(0).getExtent();

        var pixel = ol.extent.getIntersection(imageExtent, extent);
        var rotation = viewerObject.map.getView().getRotation();

        // fill form with coordinates
        $('#addToBasketForm #startX').val(Math.round(pixel[0]));
        $('#addToBasketForm #startY').val(Math.round(pixel[1]));
        $('#addToBasketForm #endX').val(Math.round(pixel[2]));
        $('#addToBasketForm #endY').val(Math.round(pixel[3]));
        $('#addToBasketForm #rotation').val(Math.round(viewerObject.radianToDegree(rotation)));
        //

        viewerObject.map.removeLayer(viewerObject.map.getLayers()[1]);
        viewerObject.map.removeInteraction(viewerObject.selectionInteraction);
    });

    var selectionLayer = new ol.layer.Vector({
        source: this.source
    });

    this.map.addLayer(selectionLayer);
    this.map.addInteraction(this.selectionInteraction);

    
}

dlfViewer.prototype.resetCropSelection = function () {
	this.source.clear();
}

dlfViewer.prototype.activateMagnifier = function () {
	if (!this.magnifierEnabled) {
		this.magnifierEnabled = true;
	    // not a map with all controls
	    // only for viewing
	    var extent = [0, 0, 1000, 1000];
	    var projection = new ol.proj.Projection({
	        code: 'xkcd-image',
	        units: 'pixels',
	        extent: extent
	    });

	    this.ov_view = new ol.View({
	        projection: projection,
	        center: ol.extent.getCenter(extent),
	        zoom: 3
	    });

	    // var ov_view = view;
	    // ov_view.setZoom(view.getZoom()+2);
	    var ov_map = new ol.Map({
	      target: 'ov_map',
	      view: this.ov_view,
	      controls: [],
	      interactions: []
	    });
	    var ov_layer = this.imageLayer;
	    ov_map.addLayer(ov_layer);

	    var mousePosition = null;
	    var dlfViewer = this;
	    this.map.on('pointermove', function (evt) {
	        // console.log("TEST");
	        mousePosition = dlfViewer.map.getEventCoordinate(evt.originalEvent);
	        dlfViewer.ov_view.setCenter(mousePosition);

	        // console.log(mousePosition);
	        // map.render();
	    });
	} else {
		$('#ov_map').html('');
		this.magnifierEnabled = false;
	}
}



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
ol.inherits(Drag, ol.interaction.Pointer);

/**
 * @param {ol.MapBrowserEvent} evt Map browser event.
 * @return {boolean} `true` to start the drag sequence.
 */
Drag.prototype.handleDownEvent = function(evt) {
  var map = evt.map;

  var feature = map.forEachFeatureAtPixel(evt.pixel,
      function(feature, layer) {
        return feature;
      });

  if (feature) {
    this.coordinate_ = evt.coordinate;
    this.feature_ = feature;
  }

  return !!feature;
};


/**
 * @param {ol.MapBrowserEvent} evt Map browser event.
 */
Drag.prototype.handleDragEvent = function(evt) {
  var map = evt.map;

  var feature = map.forEachFeatureAtPixel(evt.pixel,
      function(feature, layer) {
        return feature;
      });

  var deltaX = evt.coordinate[0] - this.coordinate_[0];
  var deltaY = evt.coordinate[1] - this.coordinate_[1];

  var geometry = /** @type {ol.geom.SimpleGeometry} */
      (this.feature_.getGeometry());
  geometry.translate(deltaX, deltaY);

  this.coordinate_[0] = evt.coordinate[0];
  this.coordinate_[1] = evt.coordinate[1];
};


/**
 * @param {ol.MapBrowserEvent} evt Event.
 */
Drag.prototype.handleMoveEvent = function(evt) {
  if (this.cursor_) {
    var map = evt.map;
    var feature = map.forEachFeatureAtPixel(evt.pixel,
        function(feature, layer) {
          return feature;
        });
    var element = evt.map.getTargetElement();
    if (feature) {
      if (element.style.cursor != this.cursor_) {
        this.previousCursor_ = element.style.cursor;
        element.style.cursor = this.cursor_;
      }
    } else if (this.previousCursor_ !== undefined) {
      element.style.cursor = this.previousCursor_;
      this.previousCursor_ = undefined;
    }
  }
};


/**
 * @param {ol.MapBrowserEvent} evt Map browser event.
 * @return {boolean} `false` to stop the drag sequence.
 */
Drag.prototype.handleUpEvent = function(evt) {
	console.log(this);
	var extent = this.feature_.getGeometry().getExtent();
	var imageExtent = evt.map.getLayers().item(0).getExtent();

	var pixel = ol.extent.getIntersection(imageExtent, extent);
	var rotation = evt.map.getView().getRotation();

	// fill form with coordinates
	if (pixel[0] != "Infinity" && pixel[1] != "Infinity" && pixel[2] != "Infinity" && pixel[3] != "Infinity") {
		$('#addToBasketForm #startX').val(Math.round(pixel[0]));
		$('#addToBasketForm #startY').val(Math.round(pixel[1]));
		$('#addToBasketForm #endX').val(Math.round(pixel[2]));
		$('#addToBasketForm #endY').val(Math.round(pixel[3]));
		$('#addToBasketForm #rotation').val(Math.round(this.radianToDegree(rotation)));
	}
	//
        
	this.coordinate_ = null;
	this.feature_ = null;
	return false;
};

/**
 * convert radian to degree
 */
Drag.prototype.radianToDegree = function(radian) {
    return radian * (180 / Math.PI);
}
