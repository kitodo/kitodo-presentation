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

                this.handleTextBlockElements(hoverSourceTextblock_, selectSource_, textblockFeature);

                this.handleTextLineElements(hoverSourceTextline_, textlineFeature);

            },
        this)
    };

    // get anchor for activate fulltext
    var anchorEl = $('#tx-dlf-tools-fulltext');

    // activate the fulltext behavior
    this.activate(anchorEl);

};

/**
 * Handle TextBlock elements
 * @param {Object} hoverSourceTextblock
 * @param {Object} selectSource
 * @þaram {ol.Feature} textblockFeature
 */
dlfViewerFullTextControl.prototype.handleTextBlockElements = function(hoverSourceTextblock, selectSource, textblockFeature) {
    var activeSelectTextBlockEl_ = this.getFeature(selectSource),
        activeHoverTextBlockEl_ = this.getFeature(hoverSourceTextblock),
        isFeatureEqualSelectFeature_ = this.isFeatureEqual(activeSelectTextBlockEl_, textblockFeature),
        isFeatureEqualToOldHoverFeature_ = this.isFeatureEqual(activeHoverTextBlockEl_, textblockFeature);

    if (!isFeatureEqualToOldHoverFeature_ && !isFeatureEqualSelectFeature_) {

        // remove old textblock hover features
        hoverSourceTextblock.clear();

        if (textblockFeature) {
            // add textblock feature to hover
            hoverSourceTextblock.addFeature(textblockFeature);
        }

    }
};

/**
 * Handle TextLine elements
 * @param {Object} hoverSourceTextline
 * @þaram {ol.Feature} textlineFeature
 */
dlfViewerFullTextControl.prototype.handleTextLineElements = function(hoverSourceTextline, textlineFeature) {
    var activeHoverTextBlockEl_ = this.getFeature(hoverSourceTextline),
        isFeatureEqualToOldHoverFeature_ = this.isFeatureEqual(activeHoverTextBlockEl_, textlineFeature);

    if (!isFeatureEqualToOldHoverFeature_) {

        if (activeHoverTextBlockEl_) {

            // remove highlight effect on fulltext view
            var oldTargetElem = $('#' + activeHoverTextBlockEl_.getId());

            if (oldTargetElem.hasClass('highlight') ) {
                oldTargetElem.removeClass('highlight');
            }

            // remove old textline hover features
            hoverSourceTextline.clear();

        }

        if (textlineFeature) {

            // add highlight effect to fulltext view
            var targetElem = $('#' + textlineFeature.getId());

            if (targetElem.length > 0 && !targetElem.hasClass('highlight')) {
                targetElem.addClass('highlight');
                $('#tx-dlf-fulltextselection').scrollTo(targetElem, 50);
                hoverSourceTextline.addFeature(textlineFeature);
            }

        }

    }
};

/**
 * Get feature from given source
 * @param {Object} source
 */
dlfViewerFullTextControl.prototype.getFeature = function(source) {
    return source.getFeatures().length > 0 ? source.getFeatures()[0] : undefined;
};

/**
 * Check if given feature element is equal to other feature
 * @param {ol.Feature} element
 * @þaram {ol.Feature} feature
 */
dlfViewerFullTextControl.prototype.isFeatureEqual = function(element, feature) {
    return element !== undefined && feature !== undefined && element.getId() === feature.getId();
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
    $("#tx-dlf-tools-fulltext").addClass(className);

    $('#tx-dlf-fulltextselection').addClass(className);
    $('#tx-dlf-fulltextselection').show();
    $('body').addClass(className);
};

/**
 * Method fetches the fulltext data from the server
 * @param {string} url
 * @param {Object} image
 * @param {number=} optOffset
 * @return {ol.Feature|undefined}
 * @static
 */
dlfViewerFullTextControl.fetchFulltextDataFromServer = function(url, image, optOffset){
    // fetch data from server
    var request = $.ajax({
        url,
        async: false
    });

    // parse alto data
    var offset = dlfUtils.exists(optOffset) ? optOffset : undefined,
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
                this.appendTextLine(textlines[j]);
            }
            $('#tx-dlf-fulltextselection').append('<br /><br />');
        }

    }

};

/**
 * Append text line
 *
 * @param {string} textLine
 */
dlfViewerFullTextControl.prototype.appendTextLine = function(textLine) {
    var textLineSpan = $('<span class="textline" id="' + textLine.getId() + '">');
    var content = textLine.get('content');

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
};
