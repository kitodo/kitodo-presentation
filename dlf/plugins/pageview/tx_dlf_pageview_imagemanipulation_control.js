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
 * @constructor
 * @param {Object=} opt_options Control options.
 */
dlfViewerImageManipulationControl = function(opt_options) {

  var options = opt_options || {};

  /**
   * @type {Array.<ol.layer.Layer>}
   * @private
   */
  this.layers = options.layers;

  /**
   * @type {Object}
   * @private
   */
  this.dic = $('#tx-dlf-tools-imagetools').length > 0 && $('#tx-dlf-tools-imagetools').attr('data-dic') ?
	    	dlfUtils.parseDataDic($('#tx-dlf-tools-imagetools')) :
	    {'imagemanipulation-on':'Activate image manipulation', 'imagemanipulation-off':'Dectivate image manipulation',
		  'saturation':'Saturation', 'hue':'Hue', 'brightness': 'Brightness', 'contrast':'Contrast', 'reset': 'Reset'};

  /**
   * @type {Element}
   * @private
   */
  this.mainMap = $('#' + options.mapContainer)[0];

  /**
   * @type {ol.Map}
   * @private
   */
  this.referenceMap = options.referenceMap;

  /**
   * @type {string}
   * @private
   */
  this.manipulationMapId = 'tx-dfgviewer-map-manipulate';

  /**
	 * @type {ol.Map|undefined}
	 * @private
	 */
	this.manipulationMap;

  /**
   * @type {ol.View}
   * @private
   */
  this.mapView = options.view;

  /**
   * @type {Element}
   * @private
   */
  this.anchor_ = $('<a/>', {
	  href: '#image-manipulation',
	  text: this.dic['imagemanipulation-on'],
	  title: this.dic['imagemanipulation-on']
  });
  $(options.target).append(this.anchor_);

  /**
   * @type {Element}
   * @private
   */
  this.toolContainerEl_ = dlfUtils.exists(options.toolContainer) ? options.toolContainer: $('.tx-dlf-toolbox')[0];

  var openToolbox = $.proxy(function(event) {
	  event.preventDefault();

	  if ($(event.target).hasClass('active')){
		  this.deactivate();
		  return;
	  }

	  this.activate();
  }, this);


  $(this.anchor_).on('click', openToolbox);
  $(this.anchor_).on('touchstart', openToolbox);

};

/**
 * Activates the image manipulation tool
 */
dlfViewerImageManipulationControl.prototype.activate = function(){
	var map;

	$.when($(this.mainMap)
		// fadout parent map container
		.hide())
		// now create new map
		.done($.proxy(function() {
			if ($('#' + this.manipulationMapId).length == 0) {
				// create manipulation map
				var imageManipulationMapEl = $('<div id="' + this.manipulationMapId + '" class="tx-dlf-map"></div>');
				$(this.mainMap.parentElement).append(imageManipulationMapEl);

				this.manipulationMap = new ol.Map({
		            layers: this.layers,
		            target: this.manipulationMapId,
		            controls: [],
		            interactions: [
		                new ol.interaction.DragPan(),
		                new ol.interaction.MouseWheelZoom(),
		                new ol.interaction.KeyboardPan(),
		                new ol.interaction.KeyboardZoom
		            ],
		            // necessary for proper working of the keyboard events
		            keyboardEventTarget: document,
		            view: this.mapView,
		            renderer: 'webgl'
		        });

		    	// couple both map objects
		    	var adjustViews = function(sourceView, destMap) {
				var rotateDiff = sourceView.getRotation() !== destMap.getView().getRotation();
				var resDiff = sourceView.getResolution() !== destMap.getView().getResolution();
				var centerDiff = sourceView.getCenter() !== destMap.getView().getCenter();

		    		if (rotateDiff || resDiff || centerDiff)
			    		destMap.zoomTo(sourceView.getCenter(),
			    				sourceView.getZoom(), 50);
		    		},
		    		adjustViewHandler = function(event) {
		    			adjustViews(event.target, this);
		    		};

		    	// when deactivate / activate adjust both map centers / zoom
		    	$(this).on("activate-imagemanipulation", $.proxy(function(event, map) {
			    	this.referenceMap.getView().on('change:resolution', adjustViewHandler, this.manipulationMap);
		    		adjustViews(this.referenceMap.getView(), this.manipulationMap);
		    	}, this));
		    	$(this).on("deactivate-imagemanipulation", $.proxy(function(event, map) {
			    	this.referenceMap.getView().un('change:resolution', adjustViewHandler, this.manipulationMap);
		    		adjustViews(this.manipulationMap.getView(), this.referenceMap);
		    	}, this));

			}

			$('#' + this.manipulationMapId).show();

			// trigger open event
			$(this).trigger("activate-imagemanipulation", this.manipulationMap);
		}, this));

	// add activate class to control element
	$(this.anchor_).addClass('active')
		.text(this.dic['imagemanipulation-off'])
		.attr('title', this.dic['imagemanipulation-off']);

	if (dlfUtils.exists(this.sliderContainer_)) {
		$(this.sliderContainer_).show().addClass('open');
	} else {
		this.sliderContainer_ = this.initializeSliderContainer_(this.toolContainerEl_);

		// fade in
		$(this.sliderContainer_).show().addClass('open');
	}
};

/**
 * @param {string} className
 * @param {string} orientation
 * @param {Function} updateFn
 * @param {number=} opt_baseValue
 * @param {string=} opt_title
 * @return {Element}
 * @private
 */
dlfViewerImageManipulationControl.prototype.createSlider_ = function(className, orientation, updateFn, opt_baseValue, opt_title){
	var title = dlfUtils.exists('opt_title') ? opt_title : '',
		sliderEl = $('<div class="slider slider-imagemanipulation ' + className + '" title="' + title + '"></div>'),
		baseMin = 0,
		baseMax = 100,
		minValueEl,
		maxValueEl,
		startValue = dlfUtils.exists(opt_baseValue) ? opt_baseValue : 100;

	/**
	 * 	@param {number} value
	 *	@param {Element} element
	 */
	var updatePosition = function(value, element){
		if (orientation == 'vertical') {
			var style_top = 100 - ((value - baseMin) / (baseMax - baseMin) * 100);
			element.style.top = style_top + '%';
			element.innerHTML = value + '%';
			return;
		}

		var style_left = (value - baseMin) / (baseMax - baseMin) * 100;
		element.style.left = style_left + '%';
		element.innerHTML = value + '%';
	};

	$(sliderEl).slider({
        'min': 0,
        'max': 100,
        'value': startValue,
        'animate': 'slow',
        'orientation': orientation,
        'step': 1,
        'slide': function( event, ui ) {
        	var value = ui['value'];
        	updatePosition(value, valueEl[0]);
        	updateFn(value);
        },
        'change': $.proxy(function( event, ui ){
        	var value = ui['value'];
        	updatePosition(value, valueEl[0]);
        	updateFn(value);
        }, this)
    });

	// append tooltips
	var innerHtml = dlfUtils.exists(opt_baseValue) ? opt_baseValue + '%' : '100%',
		valueEl = $('<div class="tooltip value ' + className + '">' + innerHtml + '</div>');
	$(sliderEl).append(valueEl);

	return sliderEl;
};

/**
 * Deactivates the image manipulation control
 */
dlfViewerImageManipulationControl.prototype.deactivate = function(){

	$(this.anchor_).removeClass('active')
		.text(this.dic['imagemanipulation-on'])
		.attr('title', this.dic['imagemanipulation-on']);

	// fadeIn parent map container
	$('#' + this.manipulationMapId).hide();
	$(this.mainMap).show();

	$(this.sliderContainer_).hide().removeClass('open');

	// trigger close event but only if an manipulation map has already been initialize
	if (this.manipulationMap !== undefined)
		$(this).trigger("deactivate-imagemanipulation", this.manipulationMap);
};

/**
 * @param {Element} parentEl
 * @private
 */
dlfViewerImageManipulationControl.prototype.initializeSliderContainer_ = function(parentEl){

	// create outer container
	var outerContainer = $('<div class="image-manipulation ol-unselectable"></div>');
	$(parentEl).append(outerContainer);

	// create inner slider container
	var sliderContainer = $('<div class="slider-container" style="display:none;"></div>');
	$(outerContainer).append(sliderContainer);

	// add contrast slider
	var contrastSlider = this.createSlider_('slider-contrast', 'horizontal', $.proxy(function(value){
		for (var i = 0; i < this.layers.length; i++) {
			this.layers[i].setContrast(value/100);
		}
	}, this), undefined, this.dic['contrast']);
	$(sliderContainer).append(contrastSlider);

	// add satuartion slider
	var satSlider = this.createSlider_('slider-saturation', 'horizontal', $.proxy(function(value){
		for (var i = 0; i < this.layers.length; i++) {
			this.layers[i].setSaturation(value/100);
		}
	}, this), undefined, this.dic['saturation']);
	$(sliderContainer).append(satSlider);

	// add brightness slider
	var brightSlider = this.createSlider_('slider-brightness', 'horizontal', $.proxy(function(value){
		var linarMapping = 2 * value / 100 -1;
		for (var i = 0; i < this.layers.length; i++) {
			this.layers[i].setBrightness(linarMapping);
		}
	}, this), 50, this.dic['brightness']);
	$(sliderContainer).append(brightSlider);

	// add hue slider
	var hueSlider = this.createSlider_('slider-hue', 'horizontal', $.proxy(function(value){
		var mapping = (value - 50) * 0.25,
			hueValue = mapping == 0 ? 0 : mapping + this.layers[0].getHue();
		for (var i = 0; i < this.layers.length; i++) {
			this.layers[i].setHue(hueValue);
		}
	}, this), 50, this.dic['hue']);
	$(sliderContainer).append(hueSlider);

	// button for reset to default state
	var resetBtn = $('<button class="reset-btn" title="' + this.dic['reset'] + '">' + this.dic['reset'] + '</button>');
	$(sliderContainer).append(resetBtn);

	var defaultValues = {
		hue: 0,
		brightness:0,
		contrast: 1,
		saturation: 1
	};

	$(resetBtn).on('click', $.proxy(function(e){
		// reset the layer
		for (var i = 0; i < this.layers.length; i++) {
			this.layers[i].setContrast(defaultValues.contrast);
			this.layers[i].setHue(defaultValues.hue);
			this.layers[i].setBrightness(defaultValues.brightness);
			this.layers[i].setSaturation(defaultValues.saturation);
		}

		// reset the sliders
		var sliderEls = $('.slider-imagemanipulation');
		for (var i = 0; i < sliderEls.length; i++){
			var sliderEl = sliderEls[i];
			var resetValue = $(sliderEl).hasClass('slider-hue') || $(sliderEl).hasClass('slider-brightness') ? 50 : 100;
			$(sliderEl).slider('value', resetValue);
		}
	}, this));

	return sliderContainer;
};
