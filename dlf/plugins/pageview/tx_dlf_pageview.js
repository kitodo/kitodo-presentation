/**
 * Constructor for dlfViewer
 *
 * @return	void
 */
function dlfViewer() {

	this.map = null;

	this.images = [];

	this.controls = [];

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

		case "zoom":

			control = new OpenLayers.Control.Zoom();

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
 *
 * @return	void
 */
dlfViewer.prototype.addImage = function(url) {

	var image = new Image();

	image.src = url;

	// Wait for image to load (or timeout of 5 seconds).
	var timeout = new Date();

	timeout.setTime(timeout.getTime() + 5000);

	while (!image.complete) {

		if (new Date().getTime() > timeout.getTime()) {

			break;

		}

	}

	this.images.push({
		'url': url,
		'width': image.width,
		'height': image.height
	});

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
 * @param	string		elementId: @id of the element to render the map into
 *
 * @return	void
 */
dlfViewer.prototype.init = function(elementId) {

	var width = 0;

	var layers = [];

	// Add image layers.
	for (var i in this.images) {

		width += this.images[i].width;

		layers.push(
			new OpenLayers.Layer.Image(
				i,
				this.images[i].url,
				new OpenLayers.Bounds(0, 0, this.images[i].width, this.images[i].height),
				new OpenLayers.Size(this.images[i].width, this.images[i].height),
				{
					'displayInLayerSwitcher': false
				}
			)
		);

	}

	// Initialize OpenLayers.
	this.map = new OpenLayers.Map({
		'div': elementId,
		'controls': this.controls,
		'fractionalZoom': true,
		'minResolution': width,
		'numZoomLevels': 200
	});

	// Add default controls.
	this.map.addControl(new OpenLayers.Control.Navigation());

	// Add image layers.
	this.map.addLayers(layers);

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
 * Set OpenLayers' language
 *
 * @param	string		lang: The language code
 *
 * @return	void
 */
dlfViewer.prototype.setLang = function(lang) {

	OpenLayers.Lang.setCode(lang);

}

// Register "onunload" handler.
window.onunload = function() {

	tx_dlf_viewer.saveSettings();

}
