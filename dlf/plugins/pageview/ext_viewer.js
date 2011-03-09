function Viewer() {
	this.docId = 0;
	this.pageId = 1;
	this.imagesLeft = [];
	this.imagesRight = [];
	this.wordCoordLeft = [];
	this.wordCoordRight = [];
	this.annotationsLeft = [];
	this.annotationsRight = [];
	this.scale = 0;
	this.doublePageView = false;
	this.annotationLayer = null;
	this.annotationLayer2 = null;
	this.ocrSelectionLayer = null;
	this.ocrOverlayLayer = null;
	this.map = null;
	this.pyramid = null;
	this.ovMap = null;
	this.highlightLayer = null;
	this.teiDataUrl = "getTEI.php?index=";
	this.annotationDataUrl = "getAnnotations.php?index=";
	this.annotationUploadUrl = "uploadAnnotations.php?index=";

	// Configurable options (@see Viewer.prototype.init)
	this.imageDataLeft = "";
	this.imageDataRight = "";
	this.userName = "";
	this.userId = 0;
	this.notificationId = "tx-dlf-notifications";
	this.hlButtonId = "highlightButton";
	this.searchKeyId = "searchKey";
	this.fuzzyBoxId = "fuzzyBox";
	this.partBoxId = "partBox";
	this.options = {
		showOcrOverlay: false,
		layerSwitcher: true
	};
}

/**
 * Initializes the viewer
 * @param {Object} imageDataLeft: Image data for the left image
 * @param {Object} imageDataRight: Image data for the right image
 * @param {Object} userName: User's common name
 * @param {Object} userId: User's UID in TYPO3
 * @param {Object} options: JSON encoded array of additional options
 */
Viewer.prototype.init = function(imageDataLeft, imageDataRight, userName, userId, options){
	if (location.search != '') {
		var params = location.search.slice(1).split("&");
		var param;
		for (var i = 0; i < params.length; i++) {
			param = params[i].split("=");
			if (unescape(param[0]) == "tx_dlf[id]") {
				this.docId = parseInt(unescape(param[1]));
			}
			if (unescape(param[0]) == "tx_dlf[page]") {
				this.pageId = parseInt(unescape(param[1]));
			}
		}
	}
	this.imageDataLeft = imageDataLeft;
	this.imageDataRight = imageDataRight;
	this.doublePageView = (imageDataRight.url !== undefined);
	this.userName = userName;
	this.userId = userId;
	this.notificationId = "tx-dlf-notifications";
	this.hlButtonId = "highlightButton";
	this.searchKeyId = "searchKey";
	this.fuzzyBoxId = "fuzzyBox";
	this.partBoxId = "partBox";

	for (var i in options) {
		var value = options[i];
		if (value !== undefined) {
			this.options[i] = Boolean(parseInt(value));
		}
	}

	this.run();
}


/***
 * Loads image data from JSON
 * @param {Object} imageData: The image's data in JSON format
 */
Viewer.prototype.loadImages = function(imageData){
//	var format = new OpenLayers.Format.JSON({});
//	var data = format.read(imageData);
	var images = [];
	if (imageData != null) {
		for (var i in imageData) {
			images.push({
				size: new OpenLayers.Size(imageData[i].width, imageData[i].height),
				url: imageData[i].url
			});
		}
	}
	return images;
}

/**
 * Loads annotation data from JSON
 * @param {Object} annotationData: The annotation's data in JSON format
 * @param {Object} baseHeight wird benötigt um die Koordinaten vom TEI auf die Map umzurechnen
 * 				   der Koordinatenursprung liegt für die OCR links oben, bei OL ist er links unten
 */
Viewer.prototype.loadAnnotations = function(url, baseHeight){
	var request = OpenLayers.Request.GET({
		url: url,
		async: false
	});
	var format = new OpenLayers.Format.GeoJSON({});
	if (request.responseText.length > 0) {
		var annotations = format.read(request.responseText);
	}
	var offset = new OpenLayers.Pixel(0, 0);
	for (var i in annotations) {
		annotations[i].geometry.resize(1 / this.scale, offset, 1);
		annotations[i].flipY(baseHeight);
	}
	return annotations;
}

/**
 * Fügt eine Annotation dem Layer hinzu
 * @param {Object} annotations
 * @param {Object} offset wird verwendet wenn die Annotation zu einer rechten Seite gehört, dann wird sie verschoben
 */
Viewer.prototype.addAnnotations = function(annotations, offset){
	if ((annotations != null)) {
		for (var i in annotations) {
			var annotation = annotations[i].clone();
			annotation.shift(offset);
			this.annotationLayer.addFeatures(annotation);
		}
	}
}

/**
 * Zeigt dem Nutzer eine Nachricht an
 * @param {Object} message
 */
Viewer.prototype.showNotification = function(message){
	document.getElementById(this.notificationId).textContent = message;
}

/**
 * Zeigt das Popup für ein Feature an
 * @param {Object} feature
 */
Viewer.prototype.showPopup = function(feature){
	popupHTML = dlfViewer.generatePopupCode(feature);
	if (!feature.popup) {
		//icon will be overridden by the rules
		var icon = new OpenLayers.Icon("img/marker-blue.png", new OpenLayers.Size(10, 10), new OpenLayers.Pixel(0, 0));
		var offset = this.getOffset(feature.geometry.getBounds().getCenterLonLat());
		lonlat = feature.geometry.getBounds().getCenterLonLat();
		feature.popup = new OpenLayers.Popup.FramedCloud("fpopup_" + feature.id, lonlat, new OpenLayers.Size(300, 200), popupHTML, icon, true, this.popUpClosed);
	} else {
		feature.popup.setContentHTML(dlfViewer.generatePopupCode(feature));
		feature.popup.show();
	}
	feature.popup.temporary = (feature.layer.id == this.ocrSelectionLayer.id);
	this.map.addPopup(feature.popup);
	select.unselectAll();
}

/**
 * Bestimmt wie groß der Offset für eine bestimmte Position ist
 * Wenn ein Feature bei lonlat 700px steht es also zum rechten Blatt gehört, und die linke Seite 500px breit ist
 * so wird 500 zurückgebenen
 * @param {Object} lonlat
 */
Viewer.prototype.getOffset = function(lonlat){
	var offset = new OpenLayers.Pixel(0, 0);
	if (lonlat.lat > this.pyramid.grid[0].bounds.right) {
		offset = this.pyramid.getBaseOffsetRight();
	}
	return offset;
}

/**
 * Callback der ausgelöst wird, wenn eine Annotation ausgewählt wurde
 */
Viewer.prototype.onAnnotationSelected = function onAnnotationSelected(){
	if (this.selectedFeatures.length > 0) {
		var feature = this.selectedFeatures[0];
		dlfViewer.showPopup(feature);
	}
}

/**
 * Callback der ausgelöst wird, wenn eine Annotation hinzugefügt wurde
 * @param {Object} evt
 */
Viewer.prototype.onAnnotationAdded = function(evt){
	var feature = evt.feature;
	var text = "";
	if (!feature.attributes.annotationId) {
		var annotationId="newAnnotation_"+OpenLayers.Util.createUniqueID();
	} else {
		var annotationId=feature.attributes.annotationId;
	}
	feature.attributes = {
		text: text,
		userName: dlfViewer.userName,
		userId: dlfViewer.userId,
		annotationId: annotationId
	};
	dlfViewer.drawPoint.deactivate();
	dlfViewer.drawPolygon.deactivate();
	dlfViewer.drawPolygon2.deactivate();
	dlfViewer.showPopup(feature);
	dlfViewer.annotationLayer.redraw();
}

/**
 * Callback der ausgelöst wird, wenn der Nutzer einen Teil des Texts mit einem Polygon markiert hat
 * @param {Object} evt
 */
Viewer.prototype.onOcrSelectionAdded = function(evt){
	var feature = evt.feature;
	var text = "";
	if ((feature.geometry instanceof OpenLayers.Geometry.Polygon) && (feature.layer.id == dlfViewer.ocrSelectionLayer.id)) {
		var wordCoord = dlfViewer.wordCoordLeft;
		var offset = dlfViewer.pyramid.getBaseOffsetRight();
		if (dlfViewer.doublePageView && (feature.geometry.getCentroid().x > offset.x)) {
			wordCoord = dlfViewer.wordCoordRight;
		}
		if (wordCoord.length > 0) {
			var centerPoly = feature.geometry.getCentroid();
			for (var i = 0; i < wordCoord.length; i++) {
				centerWord = new OpenLayers.Geometry.Point((wordCoord[i].coords[0] + wordCoord[i].coords[2]) / 2, dlfViewer.size_disp.h - (wordCoord[i].coords[1] + wordCoord[i].coords[3]) / 2);
				if (dlfViewer.doublePageView && (centerPoly.x > offset.x)) {
					centerWord.x += offset.x;
				}
				if (feature.geometry.containsPoint(centerWord)) {
					text += wordCoord[i].word + " ";
				}
			}
		}
	}
	feature.attributes = {
		text: text,
		userName: dlfViewer.userName,
		userId: dlfViewer.userId
	};
	dlfViewer.drawPoint.deactivate();
	dlfViewer.drawPolygon.deactivate();
	dlfViewer.drawPolygon2.deactivate();
	dlfViewer.showPopup(feature);
}

/**
 * Generiert innerHTML für das Popup eines Features
 * @param {Object} feature
 */
Viewer.prototype.generatePopupCode = function(feature){
	var popupHTML;
	if (feature.layer.id == this.ocrSelectionLayer.id) {
		popupHTML = '<textarea id="txt_' + feature.id + '" style="width:285px; height: 120px;">' + feature.attributes.text + '</textarea><br>';
	} else if (feature.attributes.userId != this.userId) {
		popupHTML = feature.attributes.userName + " meint: <br>";
		popupHTML += '<textarea id="txt_' + feature.id + '" style="width:285px; height: 120px;">' + feature.attributes.text + '</textarea><br>';
	} else {
		popupHTML = feature.attributes.userName + " meint: <br>";
		popupHTML += '<textarea id="txt_' + feature.id + '" style="width:285px; height: 120px;">' + feature.attributes.text + '</textarea><br>';
		popupHTML += '<div style="text-align:right;"><input id="txtBtnDelete_' + feature.id + '" type="button" value="' + OpenLayers.Lang.translate('delete') + '" onclick="deleteAnnotation(\'' + feature.id + '\');">';
		popupHTML += '<input id="txtBtnSave_' + feature.id + '" type="button" value="' + OpenLayers.Lang.translate('ok') + '" onclick="dlfViewer.updateAnnotation(\'' + feature.id + '\');"></div>';
	}
	return popupHTML;
}

/**
 * Update der Annotation nachdem ihr Text geändert wurde
 * @param {Object} id
 */
Viewer.prototype.updateAnnotation = function(id){
	var feature = dlfViewer.annotationLayer.getFeatureById(id);
	//Caution XSS
	text = OpenLayers.Util.trim(OpenLayers.Util.stripHTML(document.getElementById("txt_" + id).value));
	feature.attributes.text = text;
	feature.popup.hide();
}

/**
 * Löschen der Annotation
 * @param {Object} id
 */
function deleteAnnotation(id){
	var annotation = dlfViewer.annotationLayer.getFeatureById(id);
	annotation.popup.toggle();
	dlfViewer.annotationLayer.removeFeatures(annotation);
	dlfViewer.annotationLayer.destroyFeatures(annotation);
}

/**
 * Löschen des Features, wenn es nur dazu dient markierten OCR-TExt anzuzeigen
 */
Viewer.prototype.popUpClosed = function(){
	//checks is the popup is a temporary one
	if (this.temporary) {
		var feature = dlfViewer.ocrSelectionLayer.getFeatureById(this.id);
		this.hide();
		dlfViewer.ocrSelectionLayer.removeFeatures(feature);
		dlfViewer.ocrSelectionLayer.destroyFeatures(feature);
	} else {
		this.hide();
	}
}

/**
 * Update der Ansicht, die events werden vom annotationLayer entfernt. Weil sonst das hinzufügen der Annotationen (z.b. für die ein
 * geblendete rechte Seite) dazu führt, dass automatisch die Popups geöffnet werden
 *
 */
Viewer.prototype.updatePageView = function(){
	this.annotationLayer.events.remove('featureadded');
	this.annotationLayer.events.remove('featureselected');
	this.ocrSelectionLayer.events.remove('featureadded');
	if (!this.doublePageView) {
		this.annotationsLeft = [];
		this.annotationsRight = [];
		var offset = this.pyramid.getBaseOffsetRight();
		if (offset.x == 0) {
			offset.x = this.pyramid.grid[0].bounds.right;
		}
		for (var i in this.annotationLayer.features) {
			var centroid = this.annotationLayer.features[i].geometry.getCentroid();
			if (centroid.x > offset.x) {
				var annotation = this.annotationLayer.features[i].clone();
				annotation.shift(new OpenLayers.Pixel(-offset.x, -offset.y));
				this.annotationsRight.push(annotation);
			} else {
				this.annotationsLeft.push(this.annotationLayer.features[i].clone());
			}
		}
		this.annotationLayer.destroyFeatures();
	}
	if (this.doublePageView) {
		if (this.imagesRight.length < 1) {
			this.imagesRight = this.loadImages(this.imageDataRight);
			// loading images failes
			if (this.imagesRight.length < 1) {
				this.doublePageView = false;
				this.showNotification(OpenLayers.Lang.translate('noImageFile'));
			} else {
				this.imagesRight.sort(this.pyramidSort);
				for (var i = 1; i < this.imagesRight.length; i++) {
					this.pyramid.addImageToPyramid(this.imagesRight[i].url, this.imagesRight[i].size, new OpenLayers.Bounds(0, 0, this.imagesRight[1].size.w, this.imagesRight[1].size.h), "right");
				}
				this.ovMap.layers[0].addTile(this.imagesRight[0].url, this.imagesRight[0].size, 'right');
			}
		}
		if (this.wordCoordRight.length < 1) {
			this.wordCoordRight = this.loadTEI(this.teiDataUrl + (this.pageId + 1), this.scale);
			if (this.wordCoordRight.length > 0) {
				document.getElementById(this.hlButtonId).disabled = false;
			}
		}
	}
	this.pyramid.setDoublePageView(this.doublePageView);
	this.ovMap.setDoublePageView(this.doublePageView);
	this.map.zoomToMaxExtent();
	//add annotations
	if (!this.doublePageView) {
		this.addAnnotations(this.annotationsLeft, new OpenLayers.Pixel(0, 0));
	}
	offset = this.pyramid.getBaseOffsetRight();
	if (this.doublePageView) {
		if (this.annotationsRight.length < 1) {
			this.annotationsRight = this.loadAnnotations(this.annotationDataUrl + (this.pageId + 1), this.pyramid.getBaseHeightRight());
		}
		this.addAnnotations(this.annotationsRight, offset);
	}
	this.annotationLayer.events.register('featureadded', this.annotationLayer, this.onAnnotationAdded);
	this.annotationLayer.events.register('featureselected', this.annotationLayer, this.onAnnotationSelected);
	this.ocrSelectionLayer.events.register('featureadded', this.ocrSelectionLayer, this.onOcrSelectionAdded);
}

/**
 * Wird verwendet um die Bilder entsprechend ihrer Größe zu sortieren, bevor sie der ImagePyramid hinzugefügt werden
 * @param {Object} a
 * @param {Object} b
 */
Viewer.prototype.pyramidSort = function(a, b){
	return a.size.h - b.size.h;
}

/**
 * Umstellen zwischen Einzel- und Doppelseitenansicht
 */
Viewer.prototype.toggleDoublePageView = function(){
	this.doublePageView = !(this.doublePageView && this.doublePageView);
	this.updatePageView();
	this.highlight()
}

/**
 * Fügt Bilddaten hinzu
 * @param {Object} width
 * @param {Object} height
 * @param {Object} url
 * @param {Object} position
 */
Viewer.prototype.addImage = function(width, height, url, position){
	if (position.toUpperCase() == "LEFT") {
		this.imagesLeft.push({
			size: new OpenLayers.Size(width, height),
			url: url
		});
	}
	if (position.toUpperCase() == "RIGHT") {
		this.imagesRight.push({
			size: new OpenLayers.Size(width, height),
			url: url
		});
	}
}

/**
 * Lädt die TEI Daten nach, scale wird verwendet um die Koordinaten der Wörter auf die Skale der ImagePyramid umzurechnen
 * Der genau umgedrehte Schritt passiert in exportAnnotationsToJSON
 * @param {Object} url
 * @param {Object} scale
 */
Viewer.prototype.loadTEI = function(url, scale){
	var request = OpenLayers.Request.GET({
		url: url,
		async: false
	});
	var format = new OpenLayers.Format.TEI({
		scale: scale
	});
	if (request.responseText.length > 0) {
		var wordCoords = format.read(request.responseText);
	}
	return wordCoords;
}

/**
 * Hebt ein Wort hervor
 * @param {Object} wordCoords Array mit Wörtern und deren Koordinaten
 * @param {Object} offset gibt an um wieviel Pixel die Highlightbox verschoben wird (rechte Seite)
 */
Viewer.prototype.highlightWord = function(wordCoords, offset){
	var key = document.getElementById(this.searchKeyId).value;
	var fuzzy = document.getElementById(this.fuzzyBoxId).checked;
	var part = document.getElementById(this.partBoxId).checked;
	var errorRate = 0;
	var eps1 = 0;
	var eps2 = 0.25;
	if (fuzzy) {
		errorRate = 0.3;
	}
	var similar = 0;
	var hits = 0;
	var eq = 0;
	var value;
	for (var i = 0; i < wordCoords.length; i++) {
		value = wordCoords[i].word;
		if (part || fuzzy) {
			position = this.compareStrings(key.toUpperCase(), value.toUpperCase(), part, errorRate);
		} else {
			position = value.toUpperCase() == key.toUpperCase();
			if (position == false) {
				position = -1;
			} else {
				position = 0;
			}
		}
		if (position >= 0) {
			if ((position > 0) && (value[0].toUpperCase() == value[0])) {
				eps1 = 0.5;
			} else {
				eps1 = 0;
			}
			ext = wordCoords[i].coords;
			w1 = Math.round(ext[0] + (position + eps1) / value.length * (ext[2] - ext[0]));
			w2 = Math.round(w1 + (key.length + eps2) * (ext[2] - ext[0]) / value.length);
			bounds = new OpenLayers.Bounds(w1 + offset.x, this.map.baseLayer.extent.top - ext[1] + offset.y, w2 + offset.x, this.map.baseLayer.extent.top - ext[3] + offset.y);
			box = new OpenLayers.Feature.Vector(bounds.toGeometry());
			this.highlightLayer.addFeatures(box);
		}
	}
	this.map.zoomToMaxExtent();
}

/**
 * Wrapper
 */
Viewer.prototype.highlight = function(){
	this.highlightLayer.destroyFeatures();
	if (this.wordCoordLeft.length > 0) {
		this.highlightWord(this.wordCoordLeft, new OpenLayers.Pixel(0, 0));
	}
	if ((this.doublePageView) && (this.wordCoordRight.length > 0)) {
		this.highlightWord(this.wordCoordRight, this.pyramid.getBaseOffsetRight());
	}
}

/**
 * returns -1 if the two strings are not similar
 * @param {Object} a der Key nach dem gematcht wird
 * @param {Object} b ein Wort
 * @param {Object} part darf a teilstück von b sein
 * @param {Object} errorRate wieviel prozent der buchstaben von a dürfen im schlimmsten fall nicht mit b übereinstimmen
 */
Viewer.prototype.compareStrings = function(a, b, part, errorRate){
	var shorter;
	if (!part && (a.length != b.length)) {
		return -1;
	}
	//no fuzzy
	if (part && (errorRate == 0)) {
		return b.indexOf(a);
	}
	// fuzzy
	if (a.length <= b.length) {
		shorter = a;
	} else {
		return -1;
	}
	var d = 0;
	var index = 0;
	var dOpt = b.length;
	for (var i = 0; i <= b.length - a.length; i++) {
		d = 0;
		for (var j = 0; j < a.length; j++) {
			if (a[j] != b[j + i]) {
				d++;
			}
		}
		if (d < dOpt) {
			dOpt = d;
			index = i;
			if (d == 0) {
				break;
			}
		}
	}
	if (dOpt > errorRate * a.length) {
		return -1;
	}
	return index;
}

/**
 * exportiert die annotationen als JSON und sendet diese an die entsprechende PHP-Datei
 */
Viewer.prototype.exportAnnotationsToJSON = function(){
	var dataLeft = [];
	var dataRight = [];
	var offset = this.pyramid.getBaseOffsetRight();
	if (offset.x == 0) {
		offset.x = this.pyramid.grid[0].bounds.right;
	}
	for (var i in this.annotationLayer.features) {
		var centroid = this.annotationLayer.features[i].geometry.getCentroid();
		if (centroid.x <= offset.x) {
			this.annotationLayer.features[i].flipY(this.pyramid.getBaseHeightLeft());
			this.annotationLayer.features[i].geometry.resize(this.scale, new OpenLayers.Pixel(0, 0), 1);
			dataLeft.push(this.annotationLayer.features[i]);
		} else {
			this.annotationLayer.features[i].shift(new OpenLayers.Pixel(-offset.x, -offset.y));
			this.annotationLayer.features[i].flipY(this.pyramid.getBaseHeightRight());
			this.annotationLayer.features[i].geometry.resize(this.scale, new OpenLayers.Pixel(0, 0), 1);
			dataRight.push(this.annotationLayer.features[i]);
		}
	}
	if ((!this.doublePageView) && (this.annotationsRight)) {
		for (var i in this.annotationsRight) {
			var x = this.pyramid.getBaseHeightRight();
			this.annotationsRight[i].flipY(this.pyramid.getBaseHeightRight());
			this.annotationsRight[i].geometry.resize(this.scale, new OpenLayers.Pixel(0, 0), 1);
			dataRight.push(this.annotationsRight[i]);
		}
	}
	var formater = new OpenLayers.Format.GeoJSON();
	if (dataLeft.length > 0) {
		var annotationJSONLeft = formater.write(dataLeft, true, this.imagesLeft[this.imagesLeft.length - 1].url);
		var request = OpenLayers.Request.POST({
			url: this.annotationUploadUrl,
			data: annotationJSONLeft,
			headers: {
				"Content-Type": "text/plain"
			},
			callback: this.showServerResponse
		});
		document.getElementById('output').value = annotationJSONLeft;
	}
	if (dataRight.length > 0) {
		var annotationJSONRight = formater.write(dataRight, true, this.imagesRight[this.imagesRight.length - 1].url);
		var request = OpenLayers.Request.POST({
			url: this.annotationUploadUrl,
			data: annotationJSONRight,
			headers: {
				"Content-Type": "text/plain"
			},
		});
	}
	return;
}

/**
 * Zeigt ein Overlay mit OCR-Text an, diese Funktion wird nur aufgerufen wenn eine Zoomstufe erreicht ist die groß genug ist,
 * siehe oben. Die Worte werden nur in dem aktuell betrachteten Bereich angezeigt.
 * eps .. ist ein parameter der anzeigt, wieviel Prozent auch noch neben dem Rand angezeigt werden
 * @param {Object} mapBounds .. gibt den aktuellen sichtbereich an
 */
Viewer.prototype.updateOcrOverlayLayer = function(mapBounds){
	eps = 0.01;
	mapBounds = new OpenLayers.Bounds((1 - eps) * mapBounds.left, (1 - eps) * mapBounds.bottom, (1 + eps) * mapBounds.right, (1 + eps) * mapBounds.top);
	dlfViewer.ocrOverlayLayer.destroyFeatures();
	wordCoord = dlfViewer.wordCoordLeft;
	offset = dlfViewer.pyramid.getBaseOffsetRight();
	if (dlfViewer.doublePageView && ((mapBounds.right + mapBounds.left) / 2 > offset.x)) {
		wordCoord = dlfViewer.wordCoordRight;
	} else {
		offset = new OpenLayers.Pixel(0, 0);
	}
	for (var i = 0; i < wordCoord.length; i++) {
		ext = wordCoord[i].coords;
		bounds = new OpenLayers.Bounds(ext[0] + offset.x, dlfViewer.size_disp.h - (ext[1] + offset.y), ext[2] + offset.x, dlfViewer.size_disp.h - (ext[3] + offset.y));
		center = bounds.getCenterLonLat();
		if ((mapBounds.bottom < center.lat) && (mapBounds.top > center.lat) && (mapBounds.left < center.lon) && (mapBounds.right > center.lon)) {
			box = new OpenLayers.Feature.Vector(bounds.toGeometry());
			box.attributes = {
				text: wordCoord[i].word
			};
			dlfViewer.ocrOverlayLayer.addFeatures(box);
		}
	}
}

/**
 * Hauptroutine
 */
Viewer.prototype.run = function(){
	//Loading Images, TEI
	this.imagesLeft = this.loadImages(this.imageDataLeft);
	if (this.imagesLeft.length < 1) {
		this.showNotification(OpenLayers.Lang.translate('noImageFile'));
		return;
	}
	this.imagesLeft.sort(this.pyramidSort);
	var size_orig = new OpenLayers.Size(this.imagesLeft[this.imagesLeft.length - 1].size.w, this.imagesLeft[this.imagesLeft.length - 1].size.h);
	this.size_disp = new OpenLayers.Size(this.imagesLeft[1].size.w, this.imagesLeft[1].size.h);
	this.scale = size_orig.w / this.size_disp.w;
//	if (this.wordCoordLeft < 1) {
//		document.getElementById(this.hlButtonId).disabled = true;
//	} else {
//		document.getElementById(this.hlButtonId).disabled = false;
//	}
	// creating map and add layers
	var options = {
		maxExtent: new OpenLayers.Bounds(0, 0, this.size_disp.w, this.size_disp.h),
		controls: [new OpenLayers.Control.Navigation()],
		numZoomLevels: 7,
		fractionalZoom: true
	};
	this.map = new OpenLayers.Map('dlfViewer', options);
	var bounds = new OpenLayers.Bounds(0, 0, this.size_disp.w, this.size_disp.h);
	var scale = size_orig.w / this.size_disp.w;
	//create pyramid and add images to it
	this.pyramid = new OpenLayers.Layer.ImagePyramid(OpenLayers.Lang.translate('actualPage'), null, bounds, this.size_disp);
	for (var i = 1; i < (this.imagesLeft.length); i++) {
		this.pyramid.addImageToPyramid(this.imagesLeft[i].url, this.imagesLeft[i].size, new OpenLayers.Bounds(0, 0, this.imagesLeft[1].size.w, this.imagesLeft[1].size.h), "left");
	}
	this.pyramid.adjustSizes();
	//Create the hightlighting-layer
	this.highlightLayer = new OpenLayers.Layer.Vector(OpenLayers.Lang.translate('highlightLayer'));
	// create overviewmap
	var thumbnailSize = this.imagesLeft[0].size;
	var thumbnailExtent = new OpenLayers.Bounds(0, 0, thumbnailSize.w, thumbnailSize.h)
	var thumbnail = new OpenLayers.Layer.ExtendedImage('Thumbnail', this.imagesLeft[0].url, thumbnailExtent, thumbnailSize);
	thumbnail.addTile(this.imagesLeft[0].url, this.imagesLeft[0].size, 'left');
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
	this.ovMap = new OpenLayers.Control.OverviewMap(controlOptions);
	var style = new OpenLayers.Style({name: "default"});
	var ruleOwnAnnotation = new OpenLayers.Rule({
		filter: new OpenLayers.Filter.Comparison({
			type: OpenLayers.Filter.Comparison.EQUAL_TO,
			property: "userId",
			value: this.userId,
		}),
		symbolizer: {
			//externalGraphic: "img/marker-gold.png",
			pointRadius: 7,
			fillColor: "#ffd700",
			fillOpacity: 0.7,
			strokeColor: "#ffd700",
			strokeWidth: 2
		}
	});
	var ruleOtherAnnotation = new OpenLayers.Rule({
		filter: new OpenLayers.Filter.Comparison({
			type: OpenLayers.Filter.Comparison.NOT_EQUAL_TO,
			property: "userId",
			value: this.userId,
		}),
		symbolizer: {
			//externalGraphic: "img/marker-blue.png",
			pointRadius: 7,
			fillColor: "#8da8f6",
			fillOpacity: 0.7,
			strokeColor: "#8da8f6",
			strokeWidth: 2
		}
	});
	style.addRules([ruleOwnAnnotation,ruleOtherAnnotation]);
	var styleMap=new OpenLayers.StyleMap({"default":style});
	this.annotationLayer = new OpenLayers.Layer.Vector(OpenLayers.Lang.translate('annotations'), {
		styleMap: styleMap
	});
	this.ocrSelectionLayer = new OpenLayers.Layer.Vector(OpenLayers.Lang.translate('ocrSelection'));
	if (this.options['showOcrOverlay']) {
		//Create editable Vectorlayer and Vector styles
		var overlayStyle = new OpenLayers.StyleMap({
			"default": new OpenLayers.Style({
				fillColor: "#AAA",
				strokeColor: "#777",
				label: "${text}",
				fontColor: "yellow",
				fontWeight: "bold",
				fillOpacity: 0.8
			}),
		});
		this.ocrOverlayLayer = new OpenLayers.Layer.Vector(OpenLayers.Lang.translate('ocrOverlay'), {
			styleMap: overlayStyle
		});
	}
	//Create a select feature control
	select = new OpenLayers.Control.SelectFeature([this.annotationLayer, this.ocrSelectionLayer], {
		clickout: true,
		toggle: false,
		multiple: false,
		hover: false,
	});
	//Create drawPoint
	this.drawPoint = new OpenLayers.Control.DrawFeature(this.annotationLayer, OpenLayers.Handler.Point, {
		'displayClass': 'olControlDrawFeaturePoint'
	});
	this.drawPolygon = new OpenLayers.Control.DrawFeature(this.annotationLayer, OpenLayers.Handler.Polygon, {
		'displayClass': 'olControlDrawFeaturePolygon'
	});
	//this is used to select an area with text, the ocr-text is displayed in a popup
	this.drawPolygon2 = new OpenLayers.Control.DrawFeature(this.ocrSelectionLayer, OpenLayers.Handler.Polygon, {
		'displayClass': 'olControlDrawFeaturePolygon2'
	});
	customPanel = new OpenLayers.Control.Panel({});
	customPanel.addControls([this.drawPoint, this.drawPolygon, this.drawPolygon2]);
	var loadingPanel = new OpenLayers.Control.LoadingPanel();
	var container = document.getElementById("zoomPanel");
	var zoomPanel = new OpenLayers.Control.ZoomPanel({
		div: container
	});
	var container = document.getElementById("panPanel");
	var panPanel = new OpenLayers.Control.PanPanel({
		div: container
	});
	this.map.addLayers([this.pyramid, this.highlightLayer, this.annotationLayer, this.ocrSelectionLayer]);
	this.map.setBaseLayer(this.pyramid);
	if (this.options['showOcrOverlay']) {
		this.map.addLayer(this.ocrOverlayLayer);
		this.ocrOverlayLayer.events.register("visibilitychanged", this.ocrOverlayLayer, function(evt){
			if (dlfViewer.ocrOverlayLayer.visibility && (dlfViewer.map.zoom > 1)) {
				dlfViewer.updateOcrOverlayLayer(dlfViewer.map.calculateBounds());
			} else {
				dlfViewer.ocrOverlayLayer.destroyFeatures();
			}
		});
		this.ocrOverlayLayer.events.register("movestart", this.ocrOverlayLayer, function(evt){
			dlfViewer.ocrOverlayLayer.destroyFeatures();
		});
		this.ocrOverlayLayer.events.register("moveend", this.ocrOverlayLayer, function(evt){
			dlfViewer.ocrOverlayLayer.destroyFeatures();
			OpenLayers.Console.log("dlfViewer.map.calculateBounds: " + dlfViewer.map.calculateBounds());
			if (dlfViewer.ocrOverlayLayer.visibility && (dlfViewer.map.zoom > 1)) {
				dlfViewer.updateOcrOverlayLayer(dlfViewer.map.calculateBounds());
			} else {
				dlfViewer.ocrOverlayLayer.destroyFeatures();
			}
		});
	}
	this.map.addControl(zoomPanel);
	this.map.addControl(panPanel);
	this.map.addControl(new OpenLayers.Control.MousePosition());
	this.map.addControl(customPanel);
	this.map.addControl(loadingPanel);
	if (this.options['layerSwitcher']) {
		this.map.addControl(new OpenLayers.Control.LayerSwitcher({
			activeColor: "white"
		}));
	}
	this.map.addControl(select);
	this.map.addControl(this.ovMap);
	this.map.addControl(new OpenLayers.Control.DragFeature(this.annotationLayer));
	this.ovMap.maximizeControl();
	this.map.zoomToMaxExtent();
	//add annotations for left side
	this.annotationsLeft = this.loadAnnotations(this.annotationDataUrl + this.pageId, this.pyramid.getBaseHeightLeft());
	this.addAnnotations(this.annotationsLeft, new OpenLayers.Pixel(0, 0));
	select.activate();
	if (this.doublePageView) {
		this.updatePageView();
	}
	this.annotationLayer.events.register('featureadded', this.annotationLayer, this.onAnnotationAdded);
	this.annotationLayer.events.register('featureselected', this.annotationLayer, this.onAnnotationSelected);
	this.ocrSelectionLayer.events.register('featureadded', this.ocrSelectionLayer, this.onOcrSelectionAdded);
	// align navigationPanel
	h = document.getElementById("dlfViewer").offsetWidth;
	h2 = document.getElementById("navigationPanel").offsetWidth;
	document.getElementById("navigationPanel").style.left = Math.round((h - h2) / 2) + "px";
}