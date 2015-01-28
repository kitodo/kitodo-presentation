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
	 * var OpenLayers.Layer.Vector
	 */
	this.boxLayer = null;

	/**
	 * This holds the highlightning layer
	 *
	 * var array
	 */
	this.highlightFields = [];

	/**
	 * This holds the highlightning layer
	 *
	 * var OpenLayers.Control.DrawFeature
	 */
	this.words = [];

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

				controls[i] = new OpenLayers.Control.OverviewMap();

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

				break;

			default:

				controls[i] = null;

		}

		if (controls[i] !== null) {

			// Register control.
			this.controls.push(controls[i]);

		}

	}

}

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

}


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

}


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

}

/**
 * Initialize and display the OpenLayers map with default layers
 *
 * @return	void
 */
dlfViewer.prototype.init = function() {

	var width = 0;

	var height = 0;

	var layers = [];

	var fullTextCoordinates = [];

	// Create image layers.
	for (var i in this.images) {

		layers.push(
			new OpenLayers.Layer.Image(
				i,
				this.images[i].src,
				new OpenLayers.Bounds(this.offset, 0, this.offset + this.images[i].width, this.images[i].height),
				new OpenLayers.Size(this.images[i].width / 20, this.images[i].height / 20),
				{
					'displayInLayerSwitcher': false,
					'isBaseLayer': false,
					'maxExtent': new OpenLayers.Bounds(this.offset, 0, this.images.length * (this.offset + this.images[i].width), this.images[i].height),
					'visibility': true
				}
			)
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

		if (this.fulltexts[i]) {

			fullTextCoordinates[i] = this.loadALTO(this.fulltexts[i]);

		}

	}

	// Add default controls to controls array.
	this.controls.unshift(new OpenLayers.Control.Navigation());

	this.controls.unshift(new OpenLayers.Control.Keyboard());

	// Initialize OpenLayers map.
	this.map = new OpenLayers.Map({
		'allOverlays': true,
		'controls': this.controls,
		'div': this.div,
		'fractionalZoom': true,
		'layers': layers,
		'maxExtent': new OpenLayers.Bounds(0, 0, width, height),
		'minResolution': 1.0,
		'numZoomLevels': 20,
		'units': "m"
	});

	// Position image according to user preferences.
	if (this.getCookie("tx-dlf-pageview-centerLon") !== null && this.getCookie("tx-dlf-pageview-centerLat") !== null) {

		this.map.setCenter(
			[
				this.getCookie("tx-dlf-pageview-centerLon"),
				this.getCookie("tx-dlf-pageview-centerLat")
			],
			this.getCookie("tx-dlf-pageview-zoomLevel"),
			true,
			true
		);

	} else {

		this.map.zoomToMaxExtent();

	}

	// add polygon layer if any
	if (this.highlightFields.length) {

		for (var i in this.highlightFields) {

			var polygon = this.createPolygon(this.highlightFields[i][0], this.highlightFields[i][1], this.highlightFields[i][2], this.highlightFields[i][3], 'String');

			this.addPolygonlayer(this.highlightLayer, polygon, 1);

		}

		this.map.addLayer(this.highlightLayer);

	}

	var textBlockLayer = null;

	var ocrLayer = null;

	for (var i in this.images) {

		var textBlockCoordinates = fullTextCoordinates[i];

		for (var j in textBlockCoordinates) {

			if (textBlockCoordinates[j].type == 'PrintSpace') {

				this.setOrigImage(i, textBlockCoordinates[j].coords['x2'], textBlockCoordinates[j].coords['y2']);

			} else if (textBlockCoordinates[j].type == 'TextBlock') {

				if (! textBlockLayer) {

					textBlockLayer = new OpenLayers.Layer.Vector(

						"TextBlock"

					);

				}

				var polygon = this.createPolygon(i, textBlockCoordinates[j].coords['x1'], textBlockCoordinates[j].coords['y1'], textBlockCoordinates[j].coords['x2'], textBlockCoordinates[j].coords['y2']);

				this.addPolygonlayer(textBlockLayer, polygon, 'TextBlock');

			}
			else if (textBlockCoordinates[j].type == 'String') {

				// we need to fix the coordinates for double-page view:
				textBlockCoordinates[j].coords['x1'] = textBlockCoordinates[j].coords['x1'] + (this.offset * i)/this.origImages[i].scale;
				textBlockCoordinates[j].coords['x2'] = textBlockCoordinates[j].coords['x2'] + (this.offset * i)/this.origImages[i].scale;

				this.words.push(textBlockCoordinates[j]);

			}
		}

	}
	if (textBlockLayer instanceof OpenLayers.Layer.Vector) {

		this.map.addLayer(textBlockLayer);

	}
	// add ocrLayer if present
	if (ocrLayer instanceof OpenLayers.Layer.Vector) {

		this.map.addLayer(ocrLayer);

	}

	// boxLayer layer
	this.boxLayer = new OpenLayers.Layer.Vector("OCR Selection Layer", {
          displayInLayerSwitcher: true
        });
	this.map.addLayer(this.boxLayer);

	this.box = new OpenLayers.Control.DrawFeature(this.boxLayer, OpenLayers.Handler.RegularPolygon, {
			handlerOptions: {
            sides: 4, // get a rectangular box
            irregular: true,
          }
        });

	// callback after box is drawn
	this.box.handler.callbacks.done = this.endDrag;

	this.map.addControl(this.box);

	this.box.activate();

	//~ this.map.addControl(new OpenLayers.Control.MousePosition());
	//~ this.map.addControl(new OpenLayers.Control.LayerSwitcher());
}

/**
 * Show Popup with OCR results
 *
 * @param {Object} feature
 * @param {Object} bounds
 */
dlfViewer.prototype.showPopup = function(text, bounds){

	popupHTML = '<div id="ocrText" style="width:285px; ">' + text + '</div>';

	lonlat = bounds.getCenterLonLat();

	var ocrPopup = new OpenLayers.Popup.FramedCloud (
			"popup_ocr",
			lonlat,
			null,
			popupHTML,
			null,
			true,
			this.popUpClosed
	);

	this.map.addPopup(ocrPopup);

}

/**
 * Destroy boxLayer if popup closed
 */
dlfViewer.prototype.popUpClosed = function() {

	tx_dlf_viewer.boxLayer.destroyFeatures();

	// (re-) activate box drawing
	tx_dlf_viewer.box.activate();

	this.hide();
}

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

}

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

}

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

}

/**
 * Set OpenLayers' language
 *
 * @param	string		lang: The language code
 *
 * @return	void
 */
dlfViewer.prototype.setLang = function(lang) {

	OpenLayers.Lang.setCode(lang);

}

// Register page unload handler to save user settings.
$(window).unload(function() {

	tx_dlf_viewer.saveSettings();

});

/**
 * Add hightlight field
 *
 * @param	integer x1
 * @param	integer y1
 * @param	integer x2
 * @param	integer y2
 *
 * @return	void
 */
dlfViewer.prototype.addHightlightField = function(x1, y1, x2, y2) {

	this.highlightFields.push([x1,y1,x2,y2]);

}

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

	//~ alert('image p1 ' + x1 + ' x ' + y1 + ' p2 ' + x2 + ' x ' + y2 + 'offset: ' + offset);

	if (this.origImages.length > 1 && image == 1) {

		var scale = this.origImages[1].scale;
		var height = this.images[1].height;
		var offset = this.images[0].width;

	} else {

		var scale = this.origImages[0].scale;
		var height = this.images[0].height;
		var offset = 0;

	}

	var polygon = new OpenLayers.Geometry.Polygon (
		new OpenLayers.Geometry.LinearRing (
			[
			new OpenLayers.Geometry.Point(offset + (scale * x1), height - (scale *y1)),
			new OpenLayers.Geometry.Point(offset + (scale * x2), height - (scale *y1)),
			new OpenLayers.Geometry.Point(offset + (scale * x2), height - (scale *y2)),
			new OpenLayers.Geometry.Point(offset + (scale * x1), height - (scale *y2)),
			]
		)
	)

	var feature = new OpenLayers.Feature.Vector(polygon);

	return feature;

}
/**
 * Add layer with highlighted polygon
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
			case 'TextBlock': var hightlightStyle = new OpenLayers.Style({
					strokeColor : '#cccccc',
					strokeOpacity : 0.8,
					strokeWidth : 3,
					fillColor : '#aa0000',
					fillOpacity : 0.1,
					graphicName : 'square',
					cursor : 'inherit'
				});
				break;
			case 'String': var hightlightStyle = new OpenLayers.Style({
					strokeColor : '#ee9900',
					strokeOpacity : 0.8,
					strokeWidth : 1,
					fillColor : '#ee9900',
					fillOpacity : 0.2,
					graphicName : 'square',
					cursor : 'inherit'
				});
				break;
			case 3: var hightlightStyle = new OpenLayers.Style({
					strokeColor : '#ffffff',
					strokeOpacity : 0.8,
					strokeWidth : 4,
					fillColor : '#3d4ac2',
					fillOpacity : 0.5,
					graphicName : 'square',
					cursor : 'inherit'
				});
				break;
			default: var hightlightStyle = new OpenLayers.Style({
					strokeColor : '#ee9900',
					strokeOpacity : 0.8,
					strokeWidth : 2,
					fillColor : '#ee9900',
					fillOpacity : 0.4,
					graphicName : 'square',
					cursor : 'inherit'
				});
		}

		var stylemapObj = new OpenLayers.StyleMap(
			{
				'default' : hightlightStyle
			}
		);

		layer.styleMap = stylemapObj;

		layer.addFeatures([feature]);

	}


}

/**
 * End of Box dragging
 * *
 * @return	void
 */
dlfViewer.prototype.endDrag = function(bbox) {

	var bounds = bbox.getBounds();

	var img = 0;

	// drawn box in left or right image?
	if (bounds.left > tx_dlf_viewer.offset) {
		img = 1;
	}

	var scale = tx_dlf_viewer.origImages[img].scale;

	// draw box
	tx_dlf_viewer.drawBox(bounds);

    var text = '';

    var wordCoord = tx_dlf_viewer.words;

	if (wordCoord.length > 0) {

		var size_disp = new OpenLayers.Size(tx_dlf_viewer.images[img].width, tx_dlf_viewer.images[img].height);

		// walk through all words
		for (var i = 0; i < wordCoord.length; i++) {

			// find center point of word coordinates
			centerWord = new OpenLayers.Geometry.Point(
				scale * (wordCoord[i].coords['x1'] + ((wordCoord[i].coords['x2'] - wordCoord[i].coords['x1']) / 2)),
				(size_disp.h - scale * (wordCoord[i].coords['y1'] + (wordCoord[i].coords['y2'] - wordCoord[i].coords['y1']) / 2))
				);

			// take word if center point is inside the drawn box
			if (bbox.containsPoint(centerWord)) {

				text += wordCoord[i].word + " ";

				//~ var polygon = tx_dlf_viewer.createPolygon(img, wordCoord[i].coords['x1'] - (tx_dlf_viewer.offset * img)/tx_dlf_viewer.origImages[img].scale, wordCoord[i].coords['y1'], wordCoord[i].coords['x2'] - (tx_dlf_viewer.offset * img)/tx_dlf_viewer.origImages[img].scale, wordCoord[i].coords['y2']);
				//~ tx_dlf_viewer.addPolygonlayer(tx_dlf_viewer.boxLayer, polygon, 3);

			}
		}
	}

	tx_dlf_viewer.showPopup(text, bounds);
	tx_dlf_viewer.box.deactivate();

}

/**
 * End of Box dragging
 * *
 * @return	void
 */
dlfViewer.prototype.drawBox = function(bounds) {

	var feature = new OpenLayers.Feature.Vector(bounds.toGeometry());

	this.boxLayer.addFeatures(feature);
}

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

}


/**
 * Read ALTO file and return found words
 *
 * @param {Object} url
 * @param {Object} scale
 */
dlfViewer.prototype.loadALTO = function(url, scale){

    var request = OpenLayers.Request.GET({
        url: url,
        async: false
    });

    var format = new OpenLayers.Format.ALTO({
        scale: scale
    });

    if (request.responseXML)
        var wordCoords = format.read(request.responseXML);

    return wordCoords;
}




