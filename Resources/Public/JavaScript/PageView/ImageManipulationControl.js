/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * @constructor
 * @param {Object=} options Control options.
 *  {Element} target
 *  {ol.Map} map
 */
dlfViewerImageManipulationControl = function(options) {

    /**
     *
     * @type {number}
     */
    this.counter = dlfUtils.exists(options.counter) ? options.counter : 0;

    /**
     * @type {Object}
     * @private
     */
    this.dic = $('#tx-dlf-tools-imagetools').length > 0 && $('#tx-dlf-tools-imagetools').attr('data-dic') ?
        dlfUtils.parseDataDic($('#tx-dlf-tools-imagetools')) :
        {'imagemanipulation-on':'Activate image manipulation', 'imagemanipulation-off':'Deactivate image manipulation', 'saturation':'Saturation', 'hue':'Hue', 'brightness':'Brightness', 'contrast':'Contrast', 'reset':'Reset', 'invert':'Color inverting', 'parentContainer':'.tx-dlf-imagemanipulationtool'};

    /**
     * @type {ol.Map}
     * @private
     */
    this.baseMap_ = options.map;

    /**
     * @type {Element}
     * @private
     */
    this.anchor_ = $('<a/>', {
        href: '#image-manipulation',
        text: this.dic['imagemanipulation-on'],
        title: this.dic['imagemanipulation-on']
    });
    $(options.controlTarget).append(this.anchor_);

    /**
     * @type {Element}
     * @private
     */
    this.toolContainerEl_ = dlfUtils.exists(options.toolContainer) ? options.toolContainer : $.find(this.dic['parentContainer'])[this.counter];

    //
    // Append open/close behavior to toolbox
    //
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

    //
    // Initialize the filter
    //
    var FILTERS_DEFAULT_ = {
        'brightness': 1,
        'contrast': 1,
        'hue-rotate': 0,
        'saturate': 0
    };

    /**
     * @type {Object}
     * @private
     */
    this.filters_ = $.extend({}, FILTERS_DEFAULT_);

    /**
     * @type {Object}
     * @private
     */
    this.handler_ = {
        'resetFilter': $.proxy(function() {
            // reset the checked filters
            if (this.filters_['invert']) {
                $(this.sliderContainer_).find('#invert-filter').click();
            }

            // reset the slider filters
            var sliderEls = $('.slider.slider-imagemanipulation');
            for (var i = 0; i < sliderEls.length; i++) {
                var sliderEl = sliderEls[i],
                type = sliderEl.getAttribute('data-type'),
                value = FILTERS_DEFAULT_[String(type)];

                $(sliderEl).slider('value', value);
            }
        }, this)
    };
};

/**
 * Set filter style property on map canvas.
 *
 * @param {string} filters
 */
dlfViewerImageManipulationControl.prototype.setCssFilter_ = function (filters) {
    // TODO: For some reason, we can't query the selector in constructor (not
    //       yet available) or in first rendercomplete event (just won't work).
    //       Why?
    var canvas = this.baseMap_.getTargetElement().querySelector('canvas');
    if (canvas) {
        canvas.style.filter = filters;
        canvas.style.webkitFilter = filters;
    }
};

/**
 * @private
 */
dlfViewerImageManipulationControl.prototype.applyFilters_ = function () {
    var filters = '';

    for (var filter in this.filters_) {
        if (!this.filters_.hasOwnProperty(filter)) {
            continue;
        }

        var cssValue = this.valueToCss_(filter, this.filters_[filter]);
        if (cssValue === undefined) {
            continue;
        }

        filters += filter + '(' + cssValue + ') ';
    }

    this.setCssFilter_(filters);
};


/**
 * Activates the image manipulation tool
 */
dlfViewerImageManipulationControl.prototype.activate = function(){
    // Apply filters from last time
    this.applyFilters_();

    $(this).trigger("activate-imagemanipulation", this.baseMap_);

    //
    // Toggle toolbox controls
    //
    $(this.anchor_).addClass('active')
        .text(this.dic['imagemanipulation-off'])
        .attr('title', this.dic['imagemanipulation-off']);

    if (!dlfUtils.exists(this.sliderContainer_)) {
        // in case filter sliders are not initialize yet add them
        this.createFilters_();
    }
    $(this.sliderContainer_).show().addClass('open');
};

/**
 * Setup the image manipulation filters + reset functionality
 * @private
 */
dlfViewerImageManipulationControl.prototype.createFilters_ = function() {

    // create outer container
    var outerContainer = $('<div class="image-manipulation ol-unselectable"></div>');
    $(this.toolContainerEl_).append(outerContainer);

    /**
     * Inner slider container
     * @type {Element}
     * @private
     */
    this.sliderContainer_ = $('<div class="slider-container"></div>');
    $(outerContainer).append(this.sliderContainer_);

    //
    // Create sliders for filters and append them to the toolbox
    //
    var contrastSlider = this.createSlider_('slider-contrast', 'horizontal', 'contrast',
        [1, 0, 2, 0.01], this.dic['contrast'], function(v) {
            return parseInt(v * 100 - 100);
        }),
        saturationSlider = this.createSlider_('slider-saturation', 'horizontal', 'saturate',
            [0, -1, 1, 0.01], this.dic['saturation'], function(v) {
                return parseInt(v * 100);
            }),
        brightnessSlider = this.createSlider_('slider-brightness', 'horizontal', 'brightness',
            [1, 0, 2, 0.1], this.dic['brightness'],function(v) {
                return parseInt(v * 100 - 100);
            }),
        hueSlider = this.createSlider_('slider-hue', 'horizontal', 'hue-rotate',
            [0, -180, 180, 5], this.dic['hue'], function(v) {
                return parseInt(v);
            });
    $(this.sliderContainer_).append(contrastSlider);
    $(this.sliderContainer_).append(saturationSlider);
    $(this.sliderContainer_).append(brightnessSlider);
    $(this.sliderContainer_).append(hueSlider);

    // add invert filter
    var elFilterId = 'invert-filter';
    $(this.sliderContainer_).append($('<div class="checkbox"><label><input type="checkbox" id="' + elFilterId + '">' +
         this.dic['invert'] + '</label></div>'));
    $(this.sliderContainer_).find('#' + elFilterId).on('click', $.proxy(function(event) {
        var invert = event.target.checked;

        // update filter chain
        this.setFilter_('invert', invert);
    }, this));

    // button for reset to default state
    var resetBtn = $('<button class="reset-btn" title="' + this.dic['reset'] + '">' + this.dic['reset'] + '</button>');
    $(this.sliderContainer_).append(resetBtn);
    $(resetBtn).on('click', this.handler_.resetFilter);
};

/**
 * Functions creates a slider + behavior.
 *
 * @param {string} className
 * @param {string} orientation
 * @param {string} key
 * @param {Array.<number>|undefined} opt_baseValue
 * @param {string=} optTitle
 * @param {Function=} optLabelFn
 * @return {Element}
 * @private
 */
dlfViewerImageManipulationControl.prototype.createSlider_ = function(className, orientation, key, optBaseValues, optTitle, optLabelFn){
    var title = dlfUtils.exists('optTitle') ? optTitle : '',
        sliderEl = $('<div class="slider slider-imagemanipulation ' + className + '" title="' + title + '" data-type="' +
            key +'"></div>'),
        baseMin = dlfUtils.exists(optBaseValues) ? optBaseValues[1] : 0,
        baseMax = dlfUtils.exists(optBaseValues) ? optBaseValues[2] : 100,
        steps = dlfUtils.exists(optBaseValues) ? optBaseValues[3] : 1,
        startValue = dlfUtils.exists(optBaseValues) ? optBaseValues[0] : 100;

    /**
     * @param {Object} event
     * @param {Object} ui
     */
    var update = $.proxy(function(event, ui){
        var value = ui['value'],
                element = valueEl[0],
                labelValue = dlfUtils.exists(optLabelFn) ? optLabelFn(value) : value + '%';

        if (orientation === 'vertical') {
            var styleTop = 100 - ((value - baseMin) / (baseMax - baseMin) * 100);
            element.style.top = styleTop + '%';
            element.innerHTML = labelValue;
            return;
        }

        var styleLeft = (value - baseMin) / (baseMax - baseMin) * 100;
        element.style.left = styleLeft + '%';
        element.innerHTML = labelValue;

        // update filters.
        this.setFilter_(key, value);
    }, this);

    $(sliderEl).slider({
        'min': baseMin,
        'max': baseMax,
        'value': startValue,
        'animate': 'slow',
        orientation,
        'step': steps,
        'slide': update,
        'change': update
    });

    // append tooltips
    var innerHtml = dlfUtils.exists(optLabelFn) && dlfUtils.exists(optBaseValues) ? optLabelFn(optBaseValues[0]) : '100%',
            valueEl = $('<div class="tooltip value ' + className + '">' + innerHtml + '</div>');
    $(sliderEl).append(valueEl);

    return sliderEl;
};

/**
 * Deactivates the image manipulation control
 */
dlfViewerImageManipulationControl.prototype.deactivate = function(){
    this.setCssFilter_("");

    // toggle view of image manipulation control element
    $(this.anchor_).removeClass('active')
        .text(this.dic['imagemanipulation-on'])
        .attr('title', this.dic['imagemanipulation-on']);

    $(this.sliderContainer_).hide().removeClass('open');

    // trigger close event for trigger map adjust behavior
    $(this).trigger("deactivate-imagemanipulation");
};

/**
 * Function checks if the image manipulation is in active state or not.
 *
 * @return {boolean}
 */
dlfViewerImageManipulationControl.prototype.isActive = function() {
    return $(this.anchor_).hasClass('active');
};


/**
 * @param {string} filter The filter to set
 * @param {string} value The value to set the filter to
 * @private
 */
dlfViewerImageManipulationControl.prototype.setFilter_ = function (filter, value) {
    this.filters_[filter] = value;
    this.applyFilters_();
};

/**
 * Convert filter value to its CSS representation.
 *
 * @param {string} filter
 * @param {number} value
 * @private
 * @return {string}
 */
dlfViewerImageManipulationControl.prototype.valueToCss_ = function (filter, value) {
    switch (filter) {
        case 'contrast':
        case 'brightness':
            return (value * 100).toString() + '%';

        case 'saturate':
            return ((value + 1) * 100).toString() + '%';

        case 'hue-rotate':
            return value + 'deg';

        case 'invert':
            return value ? '100%' : '0%';
    }
};
