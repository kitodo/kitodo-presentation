function Viewer(){
	this.images = [];
	this.map = null;
}

Viewer.prototype.addImage = function(width, height, url){
	this.images.push({
		size: new OpenLayers.Size(width, height),
		url: url
	});
}

Viewer.prototype.Zoom = function(z){
	this.map.zoomTo(z);
}

Viewer.prototype.saveData = function(){
	this.setCookie("zoom", this.map.getZoom());
	this.setCookie("lat", this.map.center.lat);
	this.setCookie("lon", this.map.center.lon);
}

Viewer.prototype.setCookie = function(name, value){
	var cookie_string = name + "=" + escape(value);
	var expires = new Date();
	expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));
	cookie_string += "; path=/ ; expires=" + expires.toGMTString();
	document.cookie = cookie_string;
}

Viewer.prototype.getCookie = function(cookie_name){
	var results = document.cookie.match('(^|;) ?' + cookie_name + '=([^;]*)(;|$)');
	if (results) {
		return (unescape(results[2]));
	} else {
		return null;
	}
}

Viewer.prototype.run = function(){
	this.images.sort(function(a, b){
		return a.size.h - b.size.h;
	});
	var size_orig = new OpenLayers.Size(this.images[this.images.length - 1].size.w, this.images[this.images.length - 1].size.h);
	var size_disp = new OpenLayers.Size(this.images[1].size.w, this.images[1].size.h);
	var options = {
		maxExtent: new OpenLayers.Bounds(0, 0, size_disp.w, size_disp.h),
		controls: [new OpenLayers.Control.Navigation()],
		numZoomLevels: 7,
		fractionalZoom: true,
		theme: null
	};
	this.map = new OpenLayers.Map("tx-dlf-map", options);
	var bounds = new OpenLayers.Bounds(0, 0, size_disp.w, size_disp.h);
	var scale = size_orig.w / size_disp.w;
	pyramid = new OpenLayers.Layer.ImagePyramid("tx-dlf-image", null, bounds, size_disp);
	for (var i = 1; i < this.images.length; i++) {
		pyramid.addImageToPyramid(this.images[i].url, this.images[i].size, bounds, "left");
	}
	this.map.addLayer(pyramid);
	var thumbnailSize = this.images[0].size;
	var thumbnailExtent = new OpenLayers.Bounds(0, 0, thumbnailSize.w, thumbnailSize.h)
	var thumbnail = new OpenLayers.Layer.Image("tx-dlf-thumbnail", this.images[0].url, thumbnailExtent, thumbnailSize);
	var controlOptions = {
		layers: [thumbnail],
		'size': thumbnailSize,
		isSuitableOverview: function(){
			return true;
		},
		mapOptions: {
			maxExtent: thumbnailExtent,
			autopan: true,
			minRatio: 16,
			maxRatio: 64
		}
	};
//	ovMap = new OpenLayers.Control.OverviewMap(controlOptions);
//	this.map.addControl(ovMap);
//	ovMap.maximizeControl();
//	var loadingPanel = new OpenLayers.Control.LoadingPanel();
//	this.map.addControl(loadingPanel);
//	var container = document.getElementById("tx-dlf-zoomPanel");
//	var zoomPanel = new OpenLayers.Control.ZoomPanel({
//		div: container
//	});
//	var container = document.getElementById("tx-dlf-panPanel");
//	var panPanel = new OpenLayers.Control.PanPanel({
//		div: container
//	});
//	this.map.addControl(zoomPanel);
//	this.map.addControl(panPanel);
//	var zoom = parseInt(this.getCookie("zoom"));
//	if (!zoom) {
//		this.map.zoomToMaxExtent();
//	}
//	else {
//		var lon = parseInt(this.getCookie("lon"));
//		var lat = parseInt(this.getCookie("lat"));
//		if (lon && lat) {
//			var center = new OpenLayers.LonLat(lon, lat);
//			this.map.setCenter(center, zoom, false, false);
//		}else{
//			this.map.zoomTo(zoom);
//		}
//	}
	this.map.setCenter(new OpenLayers.LonLat(0, 0), null, false, false);
	this.map.zoomToMaxExtent();

//	h = document.getElementById("tx-dlf-map").offsetWidth;
//	h2 = document.getElementById("tx-dlf-navigationPanel").offsetWidth;
//	document.getElementById("tx-dlf-navigationPanel").style.left = Math.round((h - h2) / 2) + "px";
}