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
        {
            'fulltext':'Fulltext',
            'fulltext-on':'Activate Fulltext',
            'fulltext-off':'Deactivate Fulltext',
            'activate-full-text-initially':'0',
            'full-text-scroll-element':'html, body',
            'search-hl-parameters':'tx_dlf[highlight_word]'};

    /**
     * @type {number}
     * @private
     */
    this.activateFullTextInitially = this.dic['activate-full-text-initially'] === "1" ? 1 : 0;

    /**
     * @type {string}
     * @private
     */
    this.fullTextScrollElement = this.dic['full-text-scroll-element'];

    /**
     * @type {string}
     * @private
     */
    this.searchHlParameters = this.dic['search-hl-parameters'];
    
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
                }

                this.handleLayersForClick(feature);

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
                }

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

                this.handleTextBlockElements(textblockFeature, selectSource_, hoverSourceTextblock_);
                this.handleTextLineElements(textlineFeature, hoverSourceTextline_);
            },
        this)
    };

    this.changeActiveBehaviour();
};

/**
 * Add active / deactive behavior in case of click on control depending if the full text should be activated initially.
 */
dlfViewerFullTextControl.prototype.changeActiveBehaviour = function() {
    if (this.activateFullTextInitially === 1) {
        this.addActiveBehaviourForSwitchOn();
    } else {
        this.addActiveBehaviourForSwitchOff();
    }
};

dlfViewerFullTextControl.prototype.addActiveBehaviourForSwitchOn = function() {
    var anchorEl = $('#tx-dlf-tools-fulltext');
    if (anchorEl.length > 0){
        var toogleFulltext = $.proxy(function(event) {
            event.preventDefault();

            this.activateFullTextInitially = 0;

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
        .text(this.dic['fulltext'])
        .attr('title', this.dic['fulltext']);
    
    this.activate();
};

dlfViewerFullTextControl.prototype.addActiveBehaviourForSwitchOff = function() {
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
        this.activate();
    }
};

/**
 * Handle layers for click
 * @param {ol.Feature|undefined} feature
 */
dlfViewerFullTextControl.prototype.handleLayersForClick = function(feature) {
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
};

/**
 * Handle TextBlock elements
 * @param {ol.Feature|undefined} textblockFeature
 * @param {ol.Feature|undefined} selectSource_
 * @param {any} hoverSourceTextblock_
 */
dlfViewerFullTextControl.prototype.handleTextBlockElements = function(textblockFeature, selectSource_, hoverSourceTextblock_) {
    var activeSelectTextBlockEl_ = dlfFullTextUtils.getFeature(selectSource_),
        activeHoverTextBlockEl_ = dlfFullTextUtils.getFeature(hoverSourceTextblock_),
        isFeatureEqualSelectFeature_ = dlfFullTextUtils.isFeatureEqual(activeSelectTextBlockEl_, textblockFeature),
        isFeatureEqualToOldHoverFeature_ = dlfFullTextUtils.isFeatureEqual(activeHoverTextBlockEl_);

    if (!isFeatureEqualToOldHoverFeature_ && !isFeatureEqualSelectFeature_) {
        // remove old textblock hover features
        hoverSourceTextblock_.clear();

        if (textblockFeature) {
            // add textblock feature to hover
            hoverSourceTextblock_.addFeature(textblockFeature);
        }
    }
};

/**
 * Handle TextLine elements
 * @param {ol.Feature|undefined} textlineFeature
 * @param {any} hoverSourceTextline_
 */
dlfViewerFullTextControl.prototype.handleTextLineElements = function(textlineFeature, hoverSourceTextline_) {
    var activeHoverTextBlockEl_ = dlfFullTextUtils.getFeature(hoverSourceTextline_);
        isFeatureEqualToOldHoverFeature_ = dlfFullTextUtils.isFeatureEqual(activeHoverTextBlockEl_, textlineFeature);

    if (!isFeatureEqualToOldHoverFeature_) {
        this.removeHighlightEffect(activeHoverTextBlockEl_, hoverSourceTextline_);
        this.addHighlightEffect(textlineFeature, hoverSourceTextline_);
    }
};

/**
 * Remove highlight effect from full text view
 * @param {ol.Feature|undefined} activeHoverTextBlockEl_
 * @param {any} hoverSourceTextline_
 */
dlfViewerFullTextControl.prototype.removeHighlightEffect = function(activeHoverTextBlockEl_, hoverSourceTextline_) {
    if (activeHoverTextBlockEl_) {
        var oldTargetElem = $('#' + activeHoverTextBlockEl_.getId());

        if (oldTargetElem.hasClass('highlight') ) {
            oldTargetElem.removeClass('highlight');
        }

        // remove old textline hover features
        hoverSourceTextline_.clear();
    }
};

/**
 * Add highlight effect from full text view
 * @param {ol.Feature|undefined} textlineFeature
 * @param {any} hoverSourceTextline_ 
 */
dlfViewerFullTextControl.prototype.addHighlightEffect = function(textlineFeature, hoverSourceTextline_) {
    if (textlineFeature) {
        var targetElem = $('#' + textlineFeature.getId());
        
        if (targetElem.length > 0 && !targetElem.hasClass('highlight')) {
            targetElem.addClass('highlight');
            setTimeout(this.scrollToText, 1000, targetElem, this.fullTextScrollElement);
            hoverSourceTextline_.addFeature(textlineFeature);
        }
    }
};

/**
 * Scroll to full text element if it is highlighted
 * @param {any} element 
 * @param {string} fullTextScrollElement
 */
dlfViewerFullTextControl.prototype.scrollToText = function(element, fullTextScrollElement) {
    if (element.hasClass('highlight')) {
        $(fullTextScrollElement).animate({
            scrollTop: element.offset().top
        }, 500);
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
        this.fulltextData_ = dlfFullTextUtils.fetchFullTextDataFromServer(this.url, this.image);

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
    }

    var className = 'fulltext-visible';
    $("#tx-dlf-tools-fulltext").removeClass(className)

    if(this.activateFullTextInitially === 0) {
        $("#tx-dlf-tools-fulltext")
        .text(this.dic['fulltext-on'])
        .attr('title', this.dic['fulltext-on']);
    }

    $('#tx-dlf-fulltextselection').removeClass(className);
    $('#tx-dlf-fulltextselection').hide();
    $('body').removeClass(className);

};

/**
 * Activate Fulltext Features
 */
dlfViewerFullTextControl.prototype.enableFulltextSelect = function() {

    // register event listeners
    this.map.on('click', this.handlers_.mapClick);
    this.map.on('pointermove', this.handlers_.mapHover);

    // add layers to map
    for (var key in this.layers_) {
        if (this.layers_.hasOwnProperty(key)) {
            this.map.addLayer(this.layers_[String(key)]);
        }
    }

    // show fulltext container
    var className = 'fulltext-visible';
    $("#tx-dlf-tools-fulltext").addClass(className);

    if(this.activateFullTextInitially=== 0) {
        $("#tx-dlf-tools-fulltext")
        .text(this.dic['fulltext-off'])
        .attr('title', this.dic['fulltext-off']);
    }

    $('#tx-dlf-fulltextselection').addClass(className);
    $('#tx-dlf-fulltextselection').show();
    $('body').addClass(className);
};

/**
 * Show full text
 *
 * @param {Array.<ol.Feature>|undefined} features
 */
dlfViewerFullTextControl.prototype.showFulltext = function(features) {

    if (features !== undefined) {
        $('#tx-dlf-fulltextselection').children().remove();
        for (feature of features) {
            var textLines = feature.get('textlines');
            for (textLine of textLines) {
                this.appendTextLineSpan(textLine);
            }
            $('#tx-dlf-fulltextselection').append('<br /><br />');
        }
    }
};

/**
 * Append text line span
 *
 * @param {Object} textLine
 */
dlfViewerFullTextControl.prototype.appendTextLineSpan = function(textLine) {
    var textLineSpan = $('<span class="textline" id="' + textLine.getId() + '">');
    var content = textLine.get('content');
    
    for (item of content) {
        var span = $('<span class="' + item.get('type') + '" id="' + item.getId() + '"/>');
        var spanText = item.get('fulltext');
        var spanTextLines = spanText.split(/\n/g);
        for (const [i, spanTextLine] of spanTextLines.entries()) {
            span.append(document.createTextNode(spanTextLine));
            if (i < spanTextLines.length - 1) {
                span.append($('<br />'));
            }
        }
        textLineSpan.append(span);
    }

    textLineSpan.append('<span class="textline" id="sp"> </span>');
    $('#tx-dlf-fulltextselection').append(textLineSpan);
};
