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

			this.addPolygon(this.images[0].width, this.images[0].height, this.highlightFields[i][0], this.highlightFields[i][1], this.highlightFields[i][2], this.highlightFields[i][3]);

		}

		this.map.addLayer(this.highlightLayer);

	}


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
 * @param	integer width
 * @param	integer height
 *
 * @return	void
 */
dlfViewer.prototype.addPolygon = function(width, height, x1, y1, x2, y2) {

	//~ alert('hi' + width + ' x1 ' + x1);

	if (!this.origImage) {
		this.origImage = {
			'width': width,
			'height': height
		};
	}

	var polygon = new OpenLayers.Geometry.Polygon(
				new OpenLayers.Geometry.LinearRing(
					[
					new OpenLayers.Geometry.Point((width/this.origImage.width * x1), height - (y1 * height/ this.origImage.height)),
					new OpenLayers.Geometry.Point((width/this.origImage.width * x2), height - (y1 * height/ this.origImage.height)),
					new OpenLayers.Geometry.Point((width/this.origImage.width * x2), height - (y2 * height/ this.origImage.height)),
					new OpenLayers.Geometry.Point((width/this.origImage.width * x1), height - (y2 * height/ this.origImage.height)),
					]
				)
			)

	var feature = new OpenLayers.Feature.Vector(polygon);

	if (! this.highlightLayer) {

		var layer = new OpenLayers.Layer.Vector("Words Highlightning");

		this.highlightLayer = layer;

	}

	this.highlightLayer.addFeatures([feature]);

}

/**
 * Set Original Image Size
 *
 * @param	integer width
 * @param	integer height
 *
 * @return	void
 */
dlfViewer.prototype.setOrigImage = function(width, height) {

	//~ alert(width + ' xx ' + height);

	if (width && height) {
		this.origImage = {
			'width': width,
			'height': height
		};
	} else {
		this.origImage = null;
	}

}


