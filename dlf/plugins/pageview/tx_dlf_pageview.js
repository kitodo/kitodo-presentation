// Constructor.
function Viewer() {

	this.map = null;

	this.image = null;

}

// Get cookie.
Viewer.prototype.getCookie = function(name) {

	var results = document.cookie.match("(^|;) ?"+name+"=([^;]*)(;|$)");

	if (results) {

		return unescape(results[2]);

	} else {

		return null;

	}

}

// Initialize the OpenLayers Map.
Viewer.prototype.init = function(elementId) {

	// Initialize OpenLayers.
	this.map = new OpenLayers.Map();

	var layer = new OpenLayers.Layer.Image(
		"",
		this.image.url,
		new OpenLayers.Bounds(0, 0, this.image.width, this.image.height),
		new OpenLayers.Size(this.image.width, this.image.height),
		{
			'minResolution': this.image.width,
			'numZoomLevels': 200
		}
	);

	this.map.addLayer(layer);

	this.map.render(elementId);

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

// Save current settings to cookie.
Viewer.prototype.saveSettings = function() {

	this.setCookie("tx-dlf-pageview-zoomLevel", this.map.getZoom());

	this.setCookie("tx-dlf-pageview-centerLon", this.map.getCenter().lon);

	this.setCookie("tx-dlf-pageview-centerLat", this.map.getCenter().lat);

}

// Set cookie.
Viewer.prototype.setCookie = function(name, value) {

	document.cookie = name+"="+escape(value)+"; path=/";

}

// Set image.
Viewer.prototype.setImage = function(url, width, height) {

	this.image = {
		url: url,
		width: width,
		height: height
	}

}

// Set language.
Viewer.prototype.setLang = function(lang) {

	OpenLayers.Lang.setCode(lang);

}

// Register "onunload" handler.
window.onunload = function() {

	dlfViewer.saveSettings();

}
