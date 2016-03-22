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
 * This is necessary to support the scrolling of the element into the viewport
 * in case of text hover on the map.
 *
 * @param elem
 * @param speed
 * @returns {jQuery}
 */
jQuery.fn.scrollTo = function(elem, speed) {
    $(this).animate({
        scrollTop:  $(this).scrollTop() - $(this).offset().top + $(elem).offset().top
    }, speed == undefined ? 1000 : speed);
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
    	{'fulltext-on':'Activate Fulltext','fulltext-off':'Dectivate Fulltext'};

    /**
     * @type {ol.Feature|undefined}
     * @private
     */
    this.fulltextData_ = undefined;

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.textBlockLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOL3Styles.defaultStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.textLineLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOL3Styles.invisibleStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.selectLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOL3Styles.selectStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.highlightLayerTextblock = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOL3Styles.hoverStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.highlightLayerTextLine = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOL3Styles.textlineStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.highlightLayerTextblock = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOL3Styles.hoverStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.highlightWord_ = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerOL3Styles.wordStyle()
    });

    /**
     * @type {ol.Feature}
     * @private
     */
    this.clickedFeature;

    /**
     * @type {Function}
     * @private
     */
    this.mapClickHandler = $.proxy(function(event) {

        // the click handler adds the clicked feature to a
        // select layer which could be used to create a highlight
        // effect on the map

        var feature = this.map.forEachFeatureAtPixel(event['pixel'], function(feature, layer) {
            if (feature.get('type') === 'textblock')
                return feature;
        });

        // deselect all
        if (feature === undefined) {
            this.selectLayer.getSource().clear();
            this.clickedFeature = undefined;
            this.showFulltext(undefined);
            return;
        };

        // highlight features
        if (this.clickedFeature ) {

            // remove old clicks
            this.selectLayer.getSource().removeFeature(this.clickedFeature);

        }

        if (feature) {

            // remove hover for preventing an adding of styles
            this.highlightLayerTextblock.getSource().clear();

            // add feature
            this.selectLayer.getSource().addFeature(feature);

        }

        this.clickedFeature = feature;


        if (dlfUtils.exists(feature))
            this.showFulltext(feature);

    }, this);


    var highlightSourceTextblock_ = this.highlightLayerTextblock.getSource(),
        highlightSourceTextline_ = this.highlightLayerTextLine.getSource(),
        selectLayerSource_ = this.selectLayer.getSource(),
        map_ = this.map;

    /**
     * @type {Function}
     * @private
     */
    this.mapHoverHandler = function(event) {

        // hover in case of dragging
        if (event['dragging']) {
            return;
        };

        var textblockFeature,
            textlineFeature;
        map_.forEachFeatureAtPixel(event['pixel'], function(feature, layer) {
            if (feature.get('type') === 'textblock')
                textblockFeature = feature;
            if (feature.get('type') === 'textline')
                textlineFeature = feature;
        });

        //
        // Handle TextBlock elements
        //
        var activeSelectTextBlockEl_ = selectLayerSource_.getFeatures().length > 0 ?
                selectLayerSource_.getFeatures()[0] : undefined,
            activeHoverTextBlockEl_ = highlightSourceTextblock_.getFeatures().length > 0 ?
                highlightSourceTextblock_.getFeatures()[0] : undefined,
            isFeatureEqualSelectFeature_ = activeSelectTextBlockEl_ !== undefined && textblockFeature !== undefined &&
            activeSelectTextBlockEl_.getId() === textblockFeature.getId() ? true : false,
            isFeatureEqualToOldHoverFeature_ = activeHoverTextBlockEl_ !== undefined && textblockFeature !== undefined &&
            activeHoverTextBlockEl_.getId() === textblockFeature.getId() ? true : false;

        if (!isFeatureEqualToOldHoverFeature_ && !isFeatureEqualSelectFeature_) {

            // remove old textblock hover features
            highlightSourceTextblock_.clear();

            if (textblockFeature) {
                // add textblock feature to hover
                highlightSourceTextblock_.addFeature(textblockFeature);
            }

        }

        //
        // Handle TextLine elements
        //
        var activeHoverTextBlockEl_ = highlightSourceTextline_.getFeatures().length > 0 ?
                highlightSourceTextline_.getFeatures()[0] : undefined,
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
                highlightSourceTextline_.clear();

            }

            if (textlineFeature) {

                // add highlight effect to fulltext view
                var targetElem = $('#' + textlineFeature.getId());

                if (targetElem.length > 0 && !targetElem.hasClass('highlight')) {
                    targetElem.addClass('highlight');
                    $('#tx-dlf-fulltextselection').scrollTo(targetElem, 50);
                    highlightSourceTextline_.addFeature(textlineFeature);
                }

            }

        }

    };

    // add active / deactive behavior in case of click on control
    var anchorEl = $('#tx-dlf-tools-fulltext');
    if (anchorEl.length > 0){
        var toogleFulltext = $.proxy(function(event) {
        	  event.preventDefault();

        	  if ($(event.target).hasClass('active')){
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
    if (dlfUtils.getCookie("tx-dlf-pageview-fulltext-select") == 'enabled') {
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
		this.fulltextData_ = this.fetchFulltextDataFromServer();

		// add features to fulltext layer
		this.textBlockLayer.getSource().addFeatures(this.fulltextData_.getTextblocks());
	    this.textLineLayer.getSource().addFeatures(this.fulltextData_.getTextlines());

	    // add first feature of textBlockFeatures to map
	    if (this.fulltextData_ !== undefined && this.fulltextData_.getTextblocks().length > 0) {

	        this.selectLayer.getSource().addFeature(this.fulltextData_.getTextblocks()[0]);
	        this.clickedFeature = this.fulltextData_.getTextblocks()[0];
	        this.showFulltext(this.fulltextData_.getTextblocks()[0]);

	    }

        // add highlight words
        var key = 'tx_dlf[highlight_word]',
            urlParams = dlfUtils.getUrlParams();
        if (urlParams.hasOwnProperty(key)) {
            var value = urlParams[key],
                values = value.split(';')

            var stringFeatures = this.fulltextData_.getStringFeatures();
            values.forEach($.proxy(function(value) {
                var feature = dlfUtils.searchFeatureCollectionForText(stringFeatures, value);

                if (feature !== undefined) {
                    this.highlightWord_.getSource().addFeatures([feature]);
                };
            }, this));
        };
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
 * Activate Fulltext Features
 * @param {Array.<ol.Feature>} textBlockFeatures
 * @þaram {Array.<ol.Feature>} textLineFeatures
 */
dlfViewerFullTextControl.prototype.enableFulltextSelect = function(textBlockFeatures, textLineFeatures) {

    // register event listeners
    this.map.on('click', this.mapClickHandler);
    this.map.on('pointermove', this.mapHoverHandler);

    // add layers to map
    if (dlfUtils.exists(this.textBlockLayer)) {

        // add layers to map
        this.map.addLayer(this.textBlockLayer);
        this.map.addLayer(this.textLineLayer);
        this.map.addLayer(this.highlightLayerTextblock);
        this.map.addLayer(this.selectLayer);
        this.map.addLayer(this.highlightLayerTextLine);
        this.map.addLayer(this.highlightWord_);

        // show fulltext container
        var className = 'fulltext-visible';
        $("#tx-dlf-tools-fulltext").addClass(className)
            .text(this.dic['fulltext-off'])
            .attr('title', this.dic['fulltext-off']);

        $('#tx-dlf-fulltextselection').addClass(className);
        $('#tx-dlf-fulltextselection').show();
        $('body').addClass(className);

    }

};

/**
 * Disable Fulltext Features
 *
 * @return	void
 */
dlfViewerFullTextControl.prototype.disableFulltextSelect = function() {

    // register event listeners
    this.map.un('click', this.mapClickHandler);
    this.map.un('pointermove', this.mapHoverHandler);

    // destroy layer features
    this.map.removeLayer(this.textBlockLayer);
    this.map.removeLayer(this.textLineLayer);
    this.map.removeLayer(this.highlightLayerTextblock);
    this.map.removeLayer(this.selectLayer);
    this.map.removeLayer(this.highlightLayerTextLine);
    this.map.removeLayer(this.highlightWord_);

    // clear all layers
    this.highlightLayerTextblock.getSource().clear();
    this.highlightLayerTextLine.getSource().clear();

    var className = 'fulltext-visible';
    $("#tx-dlf-tools-fulltext").removeClass(className)
        .text(this.dic['fulltext-on'])
        .attr('title', this.dic['fulltext-on']);

    $('#tx-dlf-fulltextselection').removeClass(className);
    $('#tx-dlf-fulltextselection').hide();
    $('body').removeClass(className);

};

/**
 * Method fetches the fulltext data from the server
 * @param {string} url
 * @return {ol.Feature|undefined}
 */
dlfViewerFullTextControl.prototype.fetchFulltextDataFromServer = function(){

	// fetch data from server
    var request = $.ajax({
        url: this.url,
        async: false
    });

    // parse alto data
    var parser = new dlfAltoParser(this.image),
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
 * @param {ol.Feature|undefined} feature
 */
dlfViewerFullTextControl.prototype.showFulltext = function(feature) {

    var popupHTML = '';

    if (feature !== undefined) {
        var textlines = feature.get('textlines');

        for (var i = 0; i < textlines.length; i++) {

            popupHTML = popupHTML + '<span class="textline" id="' + textlines[i].getId() + '">';

            var content = textlines[i].get('content');
            for (var j = 0; j < content.length; j++) {
                popupHTML = popupHTML + '<span class="' + content[j].get('type') + '" id="' + content[j].getId()
                    + '">' + content[j].get('fulltext').replace(/\n/g, '<br />') + '</span>';
            }

            popupHTML = popupHTML + '</span>';
        }
    };

    $('#tx-dlf-fulltextselection').html(popupHTML);

};


