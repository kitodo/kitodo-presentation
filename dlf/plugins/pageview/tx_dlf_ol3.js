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
 * @return {number|undefined}
 */
ol.Map.prototype.getZoom = function(){
    return this.getView().getZoom();
};

/**
 * Zooms in the map. Uses ol.animation for smooth zooming
 */
ol.Map.prototype.zoomIn = function() {
    var view = this.getView(),
        zoomLevel = view.getZoom() + 1,
        resolution = view.getResolution();
    this.beforeRender(ol.animation.zoom({
        'resolution': resolution,
        'duration': 500
    }));
    view.setZoom(zoomLevel);
};

/**
 * Zooms out the map
 */
ol.Map.prototype.zoomOut = function() {
    var view = this.getView(),
        zoomLevel = view.getZoom() - 1,
     resolution = view.getResolution();
    this.beforeRender(ol.animation.zoom({
        'resolution': resolution,
        'duration': 500
    }));
    view.setZoom(zoomLevel);
};

/**
 * Zooms to given point
 * @param {Array.<number>} center
 * @param {number} zoomLevel
 */
ol.Map.prototype.zoomTo = function(center, zoomLevel) {
    var view = this.getView(),
        resolution = view.getResolution();
    this.beforeRender(ol.animation.zoom({
        'resolution': resolution,
        'duration': 500
    }));
    view.setCenter(center);
    view.setZoom(zoomLevel);
};

/**
 * Rotate the map
 * @param {number} rotation
 */
ol.Map.prototype.rotate = function(rotation) {
    var view = this.getView(),
        rotate = view.getRotation() + (rotation *  Math.PI/180),
        center = view.getCenter();

    this.beforeRender(ol.animation.rotate({
        'rotation':view.getRotation(),
        'anchor':center,
        'duration':200
    }));
    view.rotate(rotate, center);
};

/**
 * Rotate the map in the left direction
 */
ol.Map.prototype.rotateLeft = function() {
    this.rotate(-5);
};

/**
 * Rotate the map in the right direction
 */
ol.Map.prototype.rotateRight = function() {
    this.rotate(5);
};

/**
 * Resets the rotation of the map
 */
ol.Map.prototype.resetRotation = function() {
    this.getView().rotate(0, this.getView().getCenter());
};

/*
 * Define a ImageManipulation class
 */
/**
 * @constructor
 * @extends {ol.control.Control}
 * @param {Object=} opt_options Control options.
 */
ol.control.ImageManipulation = function(opt_options) {

  var options = opt_options || {};

  var anchor = document.createElement('a');
  anchor.href = '#image-manipulation';
  anchor.innerHTML = 'Image-Tools';
  anchor.title = 'Bildbearbeitung aktivieren';

  /**
   * @type {Array.<ol.layer.Layer>}
   * @private
   */
  this.layers = options.layers;
  
  var toolContainerEl_ = dlfUtils.exists(options.toolContainer) ? options.toolContainer: $('.tx-dlf-toolbox')[0];
  
//  var tooltip = goog.dom.createDom('span', {'role':'tooltip','innerHTML':vk2.utils.getMsg('openImagemanipulation')})
//  goog.dom.appendChild(anchor, tooltip);
  
  var openToolbox = $.proxy(function(event) {
	  event.preventDefault();
	  
	  if ($(event.target).hasClass('active')){
		  $(event.target).removeClass('active');
		  this.close_();
		  return;
	  } 
	  
	  $(event.target).addClass('active');
	  this.open_(toolContainerEl_);
  }, this);

  
  $(anchor).on('click', openToolbox);
  $(anchor).on('touchstart', openToolbox);  

  ol.control.Control.call(this, {
    element: anchor,
    target: options.target
  });

};
ol.inherits(ol.control.ImageManipulation, ol.control.Control);

/**
 * @param {Element} parentEl
 * @private
 */
ol.control.ImageManipulation.prototype.close_ = function(){
	$(this.sliderContainer_).fadeOut().removeClass('open');
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
ol.control.ImageManipulation.prototype.createSlider_ = function(className, orientation, updateFn, opt_baseValue, opt_title){
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
		if (orientation == 'vertical'){
			var style_top = 100 - ((value - baseMin) / (baseMax - baseMin) * 100);
			element.style.top = style_top + '%';
			element.innerHTML = value + '%';
			return;
		};
		
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
        'change': goog.bind(function( event, ui ){
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
 * @param {Element} parentEl
 * @private
 */
ol.control.ImageManipulation.prototype.initializeSliderContainer_ = function(parentEl){
	
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
		};
	}, this), undefined, 'Contrast');
	$(sliderContainer).append(contrastSlider);
	
	// add satuartion slider
	var satSlider = this.createSlider_('slider-saturation', 'horizontal', $.proxy(function(value){
		for (var i = 0; i < this.layers.length; i++) {
			this.layers[i].setSaturation(value/100);
		};
	}, this), undefined, 'Saturation');
	$(sliderContainer).append(satSlider);
	
	// add brightness slider
	var brightSlider = this.createSlider_('slider-brightness', 'horizontal', $.proxy(function(value){
		var linarMapping = 2 * value / 100 -1;
		for (var i = 0; i < this.layers.length; i++) {
			this.layers[i].setBrightness(linarMapping);
		};
	}, this), 50, 'Brightness');
	$(sliderContainer).append(brightSlider);

	// add hue slider
	var hueSlider = this.createSlider_('slider-hue', 'horizontal', $.proxy(function(value){
		var mapping = (value - 50) * 0.25,
			hueValue = mapping == 0 ? 0 : mapping + this.layers[0].getHue();
		for (var i = 0; i < this.layers.length; i++) {
			this.layers[i].setHue(hueValue);
		};
	}, this), 50, 'Hue');
	$(sliderContainer).append(hueSlider);
	
	// button for reset to default state
	var resetBtn = $('<button class="reset-btn" title="Reset">Reset</button>');
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
		};
		
		// reset the sliders
		var sliderEls = $('.slider-imagemanipulation')
		for (var i = 0; i < sliderEls.length; i++){
			var sliderEl = sliderEls[i];
			var resetValue = $(sliderEl).hasClass('slider-hue') || $(sliderEl).hasClass('slider-brightness') ? 50 : 100;
			$(sliderEl).slider('value', resetValue);
		};
	}, this));
		
	return sliderContainer;
};


/**
 * @param {Element} parentEl
 * @private
 */
ol.control.ImageManipulation.prototype.open_ = function(parentEl){
	
	if (dlfUtils.exists(this.sliderContainer_)) {
		$(this.sliderContainer_).fadeIn().addClass('open');
	} else {
		this.sliderContainer_ = this.initializeSliderContainer_(parentEl);
		
		// fade in
		$(this.sliderContainer_).fadeIn().addClass('open');
	};
};
