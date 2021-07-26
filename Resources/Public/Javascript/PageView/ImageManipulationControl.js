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
 * Right know the image manipulation uses an own ol.Map object based on a webgl renderer. This is due to the fact
 * that other parts of the viewer application are using vector geometries and ol3 does only support full vector
 * renderering with the canvas and dom renderer yet. In contrast the image manipulation tool is only working
 * with a webgl renderer. Therefore it uses an own ol.Map object which is overlaid and synchronized with the
 * base ol.Map object.
 *
 * @constructor
 * @param {Object=} options Control options.
 *  {Array.<ol.layer.Layer>} layers
 *  {Element} target
 *  {ol.View} view
 *  {ol.Map} map
 */
dlfViewerImageManipulationControl = function(options) {

    /**
     * @type {Object}
     * @private
     */
    this.dic = $('#tx-dlf-tools-imagetools').length > 0 && $('#tx-dlf-tools-imagetools').attr('data-dic') ?
        dlfUtils.parseDataDic($('#tx-dlf-tools-imagetools')) :
        {'imagemanipulation-on':'Activate image manipulation', 'imagemanipulation-off':'Deactivate image manipulation', 'saturation':'Saturation', 'hue':'Hue', 'brightness':'Brightness', 'contrast':'Contrast', 'reset':'Reset', 'invert':'Color inverting', 'parentContainer':'.tx-dlf-imagemanipulationtool'};

    /**
     * @type {Array.<ol.layer.Layer>}
     * @private
     */
    this.layers = options.layers;

    /**
     * @type {ol.Map}
     * @private
     */
    this.baseMap_ = options.map;

    /**
     * @type {ol.Map|undefined}
     * @private
     */
    this.map_ = undefined;

    /**
     * @type {ol.View}
     * @private
     */
    this.view_ = options.view;

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
    this.toolContainerEl_ = dlfUtils.exists(options.toolContainer) ? options.toolContainer : $(this.dic['parentContainer'])[0];

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
        'hue': 0,
        'saturation': 0
    };

    /**
     * @type {Object}
     * @private
     */
    this.filters_ = $.extend({}, FILTERS_DEFAULT_);

    /**
     * Is filter updated
     * @type {boolean}
     * @private
     */
    this.filterUpdated_ = false;

    /**
     * @type {Object}
     * @private
     */
    this.handler_ = {
        'postcomposeImageFilter': $.proxy(function (event) {
            var webglContext = event['glContext'],
            canvas = $('#' + this.map_.getTargetElement().id + ' canvas.ol-unselectable')[0];

            if (webglContext !== undefined && webglContext !== null) {
                var gl = webglContext.getGL();

                if (this.filterUpdated_) {

                    glif.reset();

                    for (var filter in this.filters_) {
                        glif.addFilter(filter, this.filters_[String(filter)]);
                    }

                    this.filterUpdated_ = false;
                }

                glif.apply(gl, canvas);

                // for showing openlayers that the program changed
                // if missing openlayers will produce errors because it
                // expected other shaders in the webgl program
                webglContext.useProgram(undefined);
            }
        }, this),
        'resetFilter': $.proxy(function(event) {
            // reset the checked filters
            if (this.filters_.hasOwnProperty('invert')) {
                $('#invert-filter').click();
            }

            // reset the slider filters
            var sliderEls = $('.slider.slider-imagemanipulation');
            for (var i = 0; i < sliderEls.length; i++) {
                var sliderEl = sliderEls[i],
                type = sliderEl.getAttribute('data-type'),
                value = FILTERS_DEFAULT_[String(type)];

                $(sliderEl).slider('value', value);
            };
        }, this)
    };
};

/**
 * Activates the image manipulation tool
 */
dlfViewerImageManipulationControl.prototype.activate = function(){

    //
    // Toggle maps
    //
    $.when($(this.baseMap_.getTargetElement())
        // fadeOut the base map container
        .hide())
        // fadeIn image map container
        .done($.proxy(function(){
            if (!dlfUtils.exists(this.map_)) {
                // create map container and map object if not exists yet
                this.createMap_();
            }

            // Show map
            $(this.map_.getTargetElement()).show();

            // trigger open event
            $(this).trigger("activate-imagemanipulation", this.map_);
        }, this));

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

    // add postcompose listener to layers
    if (this.map_ !== undefined)
        this.map_.on('postcompose', this.handler_.postcomposeImageFilter);
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
        saturationSlider = this.createSlider_('slider-saturation', 'horizontal', 'saturation',
            [0, -1, 1, 0.01], this.dic['saturation'], function(v) {
                return parseInt(v * 100);
            }),
        brightnessSlider = this.createSlider_('slider-brightness', 'horizontal', 'brightness',
            [1, 0, 2, 0.1], this.dic['brightness'],function(v) {
                return parseInt(v * 100 - 100);
            }),
        hueSlider = this.createSlider_('slider-hue', 'horizontal', 'hue',
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
    $('#' + elFilterId).on('click', $.proxy(function(event) {
        if (event.target.checked === true && !this.filters_.hasOwnProperty('invert')) {
            // if checked add the invert filter to the filters
            this.filters_['invert'] = true;
        } else {
            // remove invert filter
            if (this.filters_.hasOwnProperty('invert')) {
                delete this.filters_['invert'];
            }
        }

        // update filter chain
        this.filterUpdated_ = true;
        this.layers[0].changed();
    }, this));

    // button for reset to default state
    var resetBtn = $('<button class="reset-btn" title="' + this.dic['reset'] + '">' + this.dic['reset'] + '</button>');
    $(this.sliderContainer_).append(resetBtn);
    $(resetBtn).on('click', this.handler_.resetFilter);
};

/**
 * Setup the map object used from the image manipulation tool and bind it to the baseMap
 * @private
 */
dlfViewerImageManipulationControl.prototype.createMap_ = function() {
    var mapEl_ = $('<div id="tx-dlf-map-manipulate" class="tx-dlf-map"></div>');
    $(this.baseMap_.getTargetElement().parentElement).append(mapEl_);

    this.map_ = new ol.Map({
        layers: this.layers,
        target: mapEl_[0].id,
        controls: [],
        interactions: [
            new ol.interaction.DragRotate(),
            new ol.interaction.DragPan(),
            new ol.interaction.DragZoom(),
            new ol.interaction.PinchRotate(),
            new ol.interaction.PinchZoom(),
            new ol.interaction.MouseWheelZoom(),
            new ol.interaction.KeyboardPan(),
            new ol.interaction.KeyboardZoom(),
            new ol.interaction.DragRotateAndZoom()
        ],
        // necessary for proper working of the keyboard events
        keyboardEventTarget: document,
        view: this.view_,
        renderer: 'webgl'
    });

    // couple map behavior with baseMap
    var adjustViews = function(sourceView, destMap) {
            var rotateDiff = sourceView.getRotation() !== destMap.getView().getRotation();
            var resDiff = sourceView.getResolution() !== destMap.getView().getResolution();
            var centerDiff = sourceView.getCenter() !== destMap.getView().getCenter();

            if (rotateDiff || resDiff || centerDiff) {
                destMap.zoomTo(sourceView.getCenter(),sourceView.getZoom(), 50);
                destMap.getView().rotate(sourceView.getRotation());
            }

        },
        adjustViewHandler = function(event) {
            adjustViews(event.target, this);
        };

    // when deactivate / activate adjust both map centers / zoom
    $(this).on("activate-imagemanipulation", $.proxy(function(event, map) {
        // pass change events for resolution and rotation to image manipulation map
        // created through external view controls
        this.baseMap_.getView().on('change:resolution', adjustViewHandler, this.map_);
        this.baseMap_.getView().on('change:rotation', adjustViewHandler, this.map_);

        // adjust the view of both maps
        adjustViews(this.baseMap_.getView(), this.map_);
    }, this));
    $(this).on("deactivate-imagemanipulation", $.proxy(function(event, map) {
        // pass change events for resolution and rotation to image manipulation map
        // created through external view controls
        this.baseMap_.getView().un('change:resolution', adjustViewHandler, this.map_);
        this.baseMap_.getView().un('change:rotation', adjustViewHandler, this.map_);

        // adjust the view of both maps
        adjustViews(this.map_.getView(), this.baseMap_);
    }, this));
};

/**
 * Functions creates a slider + behavior.
 *
 * @param {string} className
 * @param {string} orientation
 * @param {string} key
 * @param {Array.<number>|undefined} opt_baseValue
 * @param {string=} opt_title
 * @param {Function=} opt_labelFn
 * @return {Element}
 * @private
 */
dlfViewerImageManipulationControl.prototype.createSlider_ = function(className, orientation, key, opt_baseValues, opt_title, opt_labelFn){
    var title = dlfUtils.exists('opt_title') ? opt_title : '',
        sliderEl = $('<div class="slider slider-imagemanipulation ' + className + '" title="' + title + '" data-type="' +
            key +'"></div>'),
        baseMin = dlfUtils.exists(opt_baseValues) ? opt_baseValues[1] : 0,
        baseMax = dlfUtils.exists(opt_baseValues) ? opt_baseValues[2] : 100,
        steps = dlfUtils.exists(opt_baseValues) ? opt_baseValues[3] : 1,
        startValue = dlfUtils.exists(opt_baseValues) ? opt_baseValues[0] : 100;

    /**
     * @param {Object} event
     * @param {Object} ui
     */
    var update = $.proxy(function(event, ui){
        var value = ui['value'],
                layer = this.layers[0],
                element = valueEl[0],
                labelValue = dlfUtils.exists(opt_labelFn) ? opt_labelFn(value) : value + '%';

        if (orientation === 'vertical') {
            var style_top = 100 - ((value - baseMin) / (baseMax - baseMin) * 100);
            element.style.top = style_top + '%';
            element.innerHTML = labelValue;
            return;
        }

        var style_left = (value - baseMin) / (baseMax - baseMin) * 100;
        element.style.left = style_left + '%';
        element.innerHTML = labelValue;

        // update filters.
        this.filters_[key] = value;
        this.filterUpdated_ = true;
        layer.changed();
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
    var innerHtml = dlfUtils.exists(opt_labelFn) && dlfUtils.exists(opt_baseValues) ? opt_labelFn(opt_baseValues[0]) : '100%',
            valueEl = $('<div class="tooltip value ' + className + '">' + innerHtml + '</div>');
    $(sliderEl).append(valueEl);

    return sliderEl;
};

/**
 * Deactivates the image manipulation control
 */
dlfViewerImageManipulationControl.prototype.deactivate = function(){

    // toggle maps
    if (dlfUtils.exists(this.map_)) {
        $(this.map_.getTargetElement()).hide();
    }
    $(this.baseMap_.getTargetElement()).show();

    // toggle view of image manipulation control element
    $(this.anchor_).removeClass('active')
        .text(this.dic['imagemanipulation-on'])
        .attr('title', this.dic['imagemanipulation-on']);

    $(this.sliderContainer_).hide().removeClass('open');

    // remove postcompose listener to map
    if (this.map_ !== undefined)
        this.map_.un('postcompose', this.handler_.postcomposeImageFilter);

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
