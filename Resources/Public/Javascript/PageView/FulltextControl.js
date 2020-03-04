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
 * This is necessary to support the scrolling of the element into the viewport
 * in case of text hover on the map.
 *
 * @param elem
 * @param speed
 * @returns {jQuery}
 */
jQuery.fn.scrollTo = function(elem, speed) {
    var manualOffsetTop = $(elem).parent().height() / 2;
    $(this).animate({
        scrollTop:  $(this).scrollTop() - $(this).offset().top + $(elem).offset().top - manualOffsetTop
    }, speed === undefined ? 1000 : speed);
    return this;
};

/**
 * Encapsulates especially the fulltext behavior
 * @constructor
 * @param {ol.Map} map
 * @param {Object} image
 * @param {string} fulltextUrl
 */
var dlfViewerFullTextControl = function(map, image, fulltextUrl) {

    /**
     * @private
     * @type {ol.Map}
     */
    this.map = map;

    /**
     * @type {Object}
     * @private
     */
    this.image = image;

    /**
     * @type {string}
     * @private
     */
    this.url = fulltextUrl;

    /**
     * @type {Object}
     * @private
     */
    this.dic = $('#tx-dlf-tools-fulltext').length > 0 && $('#tx-dlf-tools-fulltext').attr('data-dic') ?
        dlfUtils.parseDataDic($('#tx-dlf-tools-fulltext')) :
        {'fulltext-on':'Activate Fulltext','fulltext-off':'Deactivate Fulltext'};

    /**
     * @type {ol.Feature|undefined}
     * @private
     */
    this.fulltextData_ = undefined;

    /**
     * @type {Object}
     * @private
     */
    this.layers_ = {
        textblock: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.defaultStyle()
        }),
        textline: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.invisibleStyle()
        }),
        select: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.selectStyle()
        }),
        hoverTextblock: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.hoverStyle()
        }),
        hoverTextline: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.textlineStyle()
        })
    };

    /**
     * @type {ol.Feature}
     * @private
     */
    this.selectedFeature_ = undefined;

    /**
     * @type {Object}
     * @private
     */
    this.handlers_ = {
        mapClick: $.proxy(function(event) {
                // the click handler adds the clicked feature to a
                // select layer which could be used to create a highlight
                // effect on the map

                var feature = this.map.forEachFeatureAtPixel(event['pixel'], function(feature, layer) {
                    if (feature.get('type') === 'textblock') {
                        return feature;
                    }
                });

                // deselect all
                if (feature === undefined) {
                    this.layers_.select.getSource().clear();
                    this.selectedFeature_ = undefined;
                    this.showFulltext(undefined);
                    return;
                };

                // highlight features
                if (this.selectedFeature_) {

                    // remove old clicks
                    this.layers_.select.getSource().removeFeature(this.selectedFeature_);

                }

                if (feature) {

                    // remove hover for preventing an adding of styles
                    this.layers_.hoverTextblock.getSource().clear();

                    // add feature
                    this.layers_.select.getSource().addFeature(feature);

                }

                this.selectedFeature_ = feature;


                if (dlfUtils.exists(feature)) {
                    this.showFulltext([feature]);
                }

            },
        this),
        mapHover: $.proxy(function(event) {
                // hover in case of dragging
                if (event['dragging']) {
                    return;
                };

                var hoverSourceTextblock_ = this.layers_.hoverTextblock.getSource(),
                    hoverSourceTextline_ = this.layers_.hoverTextline.getSource(),
                    selectSource_ = this.layers_.select.getSource(),
                    map_ = this.map,
                    textblockFeature,
                    textlineFeature;

                map_.forEachFeatureAtPixel(event['pixel'], function(feature, layer) {
                    if (feature.get('type') === 'textblock') {
                        textblockFeature = feature;
                    }
                    if (feature.get('type') === 'textline') {
                        textlineFeature = feature;
                    }
                });

                //
                // Handle TextBlock elements
                //
                var activeSelectTextBlockEl_ = selectSource_.getFeatures().length > 0 ?
                        selectSource_.getFeatures()[0] : undefined,
                    activeHoverTextBlockEl_ = hoverSourceTextblock_.getFeatures().length > 0 ?
                        hoverSourceTextblock_.getFeatures()[0] : undefined,
                    isFeatureEqualSelectFeature_ = activeSelectTextBlockEl_ !== undefined && textblockFeature !== undefined &&
                    activeSelectTextBlockEl_.getId() === textblockFeature.getId() ? true : false,
                    isFeatureEqualToOldHoverFeature_ = activeHoverTextBlockEl_ !== undefined && textblockFeature !== undefined &&
                    activeHoverTextBlockEl_.getId() === textblockFeature.getId() ? true : false;

                if (!isFeatureEqualToOldHoverFeature_ && !isFeatureEqualSelectFeature_) {

                    // remove old textblock hover features
                    hoverSourceTextblock_.clear();

                    if (textblockFeature) {
                        // add textblock feature to hover
                        hoverSourceTextblock_.addFeature(textblockFeature);
                    }

                }

                //
                // Handle TextLine elements
                //
                var activeHoverTextBlockEl_ = hoverSourceTextline_.getFeatures().length > 0 ?
                        hoverSourceTextline_.getFeatures()[0] : undefined,
                    isFeatureEqualToOldHoverFeature_ = activeHoverTextBlockEl_ !== undefined && textlineFeature !== undefined &&
                    activeHoverTextBlockEl_.getId() === textlineFeature.getId() ? true : false;

                if (!isFeatureEqualToOldHoverFeature_) {

                    if (activeHoverTextBlockEl_) {

                        // remove highlight effect on fulltext view
                        var oldTargetElem = $('#' + activeHoverTextBlockEl_.getId());

                        if (oldTargetElem.hasClass('highlight') ) {
                            oldTargetElem.removeClass('highlight');
                        }

                        // remove old textline hover features
                        hoverSourceTextline_.clear();

                    }

                    if (textlineFeature) {

                        // add highlight effect to fulltext view
                        var targetElem = $('#' + textlineFeature.getId());

                        if (targetElem.length > 0 && !targetElem.hasClass('highlight')) {
                            targetElem.addClass('highlight');
                            $('#tx-dlf-fulltextselection').scrollTo(targetElem, 50);
                            hoverSourceTextline_.addFeature(textlineFeature);
                        }

                    }

                }

            },
        this)
    };

    // add active / deactive behavior in case of click on control
    var anchorEl = $('#tx-dlf-tools-fulltext');
    if (anchorEl.length > 0){
        var toogleFulltext = $.proxy(function(event) {
            event.preventDefault();

            if ($(event.target).hasClass('active')) {
                this.deactivate();
                return;
            }

            this.activate();
        }, this);


        anchorEl.on('click', toogleFulltext);
        anchorEl.on('touchstart', toogleFulltext);
    }

    // set initial title of fulltext element
    $("#tx-dlf-tools-fulltext")
        .text(this.dic['fulltext-on'])
        .attr('title', this.dic['fulltext-on']);

    // if fulltext is activated via cookie than run activation methode
    if (dlfUtils.getCookie("tx-dlf-pageview-fulltext-select") === 'enabled') {
        // activate the fulltext behavior
        this.activate(anchorEl);
    }

};

/**
 * Activate Fulltext Features
 */
dlfViewerFullTextControl.prototype.activate = function() {

    var controlEl = $('#tx-dlf-tools-fulltext');

    // if the activate method is called for the first time fetch
    // fulltext data from server
    if (this.fulltextData_ === undefined)  {
        this.fulltextData_ = dlfViewerFullTextControl.fetchFulltextDataFromServer(this.url, this.image);

        if (this.fulltextData_ !== undefined) {
            // add features to fulltext layer
            this.layers_.textblock.getSource().addFeatures(this.fulltextData_.getTextblocks());
            this.layers_.textline.getSource().addFeatures(this.fulltextData_.getTextlines());

            // add first feature of textBlockFeatures to map
            if (this.fulltextData_.getTextblocks().length > 0) {
                this.layers_.select.getSource().addFeature(this.fulltextData_.getTextblocks()[0]);
                this.selectedFeature_ = this.fulltextData_.getTextblocks()[0];
                this.showFulltext(this.fulltextData_.getTextblocks());
            }
        }
    }

    // now activate the fulltext overlay and map behavior
    this.enableFulltextSelect();
    dlfUtils.setCookie("tx-dlf-pageview-fulltext-select", 'enabled');
    $(controlEl).addClass('active');

    // trigger event
    $(this).trigger("activate-fulltext", this);
};

/**
 * Activate Fulltext Features
 */
dlfViewerFullTextControl.prototype.deactivate = function() {

    var controlEl = $('#tx-dlf-tools-fulltext');

    // deactivate fulltext
    this.disableFulltextSelect();
    dlfUtils.setCookie("tx-dlf-pageview-fulltext-select", 'disabled');
    $(controlEl).removeClass('active');

    // trigger event
    $(this).trigger("deactivate-fulltext", this);
};

/**
 * Disable Fulltext Features
 *
 * @return void
 */
dlfViewerFullTextControl.prototype.disableFulltextSelect = function() {

    // register event listeners
    this.map.un('click', this.handlers_.mapClick);
    this.map.un('pointermove', this.handlers_.mapHover);

    // remove layers
    for (var key in this.layers_) {
        if (this.layers_.hasOwnProperty(key)) {
            this.map.removeLayer(this.layers_[String(key)]);
        }
    };

    var className = 'fulltext-visible';
    $("#tx-dlf-tools-fulltext").removeClass(className)
        .text(this.dic['fulltext-on'])
        .attr('title', this.dic['fulltext-on']);

    $('#tx-dlf-fulltextselection').removeClass(className);
    $('#tx-dlf-fulltextselection').hide();
    $('body').removeClass(className);

};

/**
 * Activate Fulltext Features
 * @param {Array.<ol.Feature>} textBlockFeatures
 * @þaram {Array.<ol.Feature>} textLineFeatures
 */
dlfViewerFullTextControl.prototype.enableFulltextSelect = function(textBlockFeatures, textLineFeatures) {

    // register event listeners
    this.map.on('click', this.handlers_.mapClick);
    this.map.on('pointermove', this.handlers_.mapHover);

    // add layers to map
    for (var key in this.layers_) {
        if (this.layers_.hasOwnProperty(key)) {
            this.map.addLayer(this.layers_[String(key)]);
        }
    };

    // show fulltext container
    var className = 'fulltext-visible';
    $("#tx-dlf-tools-fulltext").addClass(className)
      .text(this.dic['fulltext-off'])
      .attr('title', this.dic['fulltext-off']);

    $('#tx-dlf-fulltextselection').addClass(className);
    $('#tx-dlf-fulltextselection').show();
    $('body').addClass(className);
};

/**
 * Method fetches the fulltext data from the server
 * @param {string} url
 * @param {Object} image
 * @param {number=} opt_offset
 * @return {ol.Feature|undefined}
 * @static
 */
dlfViewerFullTextControl.fetchFulltextDataFromServer = function(url, image, opt_offset){
    // fetch data from server
    var request = $.ajax({
        url,
        async: false
    });

    // parse alto data
    var offset = dlfUtils.exists(opt_offset) ? opt_offset : undefined,
      parser = new dlfAltoParser(image, undefined, undefined, offset),
      fulltextCoordinates = request.responseXML ? parser.parseFeatures(request.responseXML) :
            request.responseText ? parser.parseFeatures(request.responseText) : [];

    if (fulltextCoordinates.length > 0) {
        return fulltextCoordinates[0];
    }

    return undefined;
};

/**
 * Activate Fulltext Features
 *
 * @param {Array.<ol.Feature>|undefined} features
 */
dlfViewerFullTextControl.prototype.showFulltext = function(features) {

    if (features !== undefined) {
        $('#tx-dlf-fulltextselection').children().remove();
        for (var i = 0; i < features.length; i++) {
            var textlines = features[i].get('textlines');
            for (var j = 0; j < textlines.length; j++) {
                var textLineSpan = $('<span class="textline" id="' + textlines[j].getId() + '">');
                var content = textlines[j].get('content');

                for (var k = 0; k < content.length; k++) {
                    var span = $('<span class="' + content[k].get('type') + '" id="' + content[k].getId() + '"/>');
                    var spanText = content[k].get('fulltext');
                    var spanTextLines = spanText.split(/\n/g);
                    for (var l = 0; l < spanTextLines.length; l++) {
                        span.append(document.createTextNode(spanTextLines[l]));
                        if (l < spanTextLines.length - 1) {
                            span.append($('<br />'));
                        }
                    }
                    textLineSpan.append(span);
                }
                $('#tx-dlf-fulltextselection').append(textLineSpan);
            }
            $('#tx-dlf-fulltextselection').append('<br /><br />');
        }

    }

};
