/***************************************************************
*  Copyright notice
*
*  (c) 2013 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
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

	this.div = "tx-dlf-map";

	this.map = null;

	this.images = [];

	this.controls = [];

	this.offset = 0;

}

/**
 * Register control to load for map
 *
 * @param	string		name: The keyword for the control
 *
 * @return	void
 */
dlfViewer.prototype.addControl = function(name) {

	var control = null;

	switch(name) {

		case "OverviewMap":

			control = new OpenLayers.Control.OverviewMap();

			break;

		case "PanPanel":

			control = new OpenLayers.Control.PanPanel();

			break;

		case "PanZoom":

			control = new OpenLayers.Control.PanZoom();

			break;

		case "PanZoomBar":

			control = new OpenLayers.Control.PanZoomBar();

			break;

		case "ZoomPanel":

			control = new OpenLayers.Control.ZoomPanel();

			break;

	}

	if (control !== null) {

		this.controls.push(control);

	}

}

/**
 * Register an image file to load into map
 *
 * @param	string		url: URL of the image file
 * @param	integer		width: The image's width
 * @param	integer		height: The image's height
 *
 * @return	void
 */
dlfViewer.prototype.addImage = function(url, width, height) {

	for (var i in this.images) {

		if (this.images[i].url == url) {

			// Add image data.
			this.images[i] = {
				'url': url,
				'width': width,
				'height': height,
				'ready': true
			};

			return;

		}

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

		if (this.images[i].ready) {

			layers.push(
				new OpenLayers.Layer.Image(
					i,
					this.images[i].url,
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

	}

	// Add default controls.
	this.controls.unshift(new OpenLayers.Control.Navigation());

	this.controls.unshift(new OpenLayers.Control.Keyboard());

	// Initialize OpenLayers.
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

}

/**
 * Load an image file
 *
 * @param	string		url: URL of the image file
 * @param	integer		position: 0 for left image, 1 for right image
 *
 * @return	void
 */
dlfViewer.prototype.loadImage = function(url, position) {

	this.images[position] = {
		'url': url,
		'width': 0,
		'height': 0,
		'ready': false
	};

	var image = new Image();

	image.onload = function () {

		tx_dlf_viewer.addImage(this.src, this.width, this.height);

	}

	image.src = url;

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

	this.div = elementId;

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

// Initialize viewer when the page is completely loaded.
window.onload = function() {

	tx_dlf_viewer.init();

}

// Register "onunload" handler.
window.onunload = function() {

	tx_dlf_viewer.saveSettings();

}
