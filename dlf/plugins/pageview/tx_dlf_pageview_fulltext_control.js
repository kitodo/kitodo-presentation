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
 * @param {string} lang
 */
var dlfViewerFullTextControl = function(map, image, fulltextUrl, lang){

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
     * @private
     * @type {string}
     */
    this.lang = lang == 'de' || lang == 'en' ? lang : 'de';
    
    /**
     * @type {Array.<Array.<ol.Feature>}
     * @private
     */
    this.fulltextData_ = [];

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.textBlockLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerFullTextControl.style.defaultStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.textLineLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerFullTextControl.style.invisibleStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.selectLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerFullTextControl.style.selectStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.highlightLayer = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerFullTextControl.style.hoverStyle()
    });

    /**
     * @private
     * @type {ol.layer.Vector}
     */
    this.highlightLayerTextLine = new ol.layer.Vector({
        'source': new ol.source.Vector(),
        'style': dlfViewerFullTextControl.style.textlineStyle()
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
            if (feature.get('type') === 'TextBlock')
                return feature;
        });

        // highlight features
        if (feature !== this.clickedFeature) {

            if (this.clickedFeature) {

                this.selectLayer.getSource().removeFeature(this.clickedFeature);

            }

            if (feature) {

                this.selectLayer.getSource().addFeature(feature);

            }

            this.clickedFeature = feature;

        }

        if (dlfUtils.exists(feature))
            this.showFulltext(feature);

    }, this);

    /**
     * @type {ol.Feature}
     * @private
     */
    this.highlightTextblockFeature;

    /**
     * @type {ol.Feature}
     * @private
     */
    this.highlightTextlineFeature;


    /**
     * @type {Function}
     * @private
     */
    this.mapHoverHandler = $.proxy(function(event) {

        if (event['dragging']) {
            return;
        };

        var textblockFeature,
            textlineFeature;
        this.map.forEachFeatureAtPixel(event['pixel'], function(feature, layer) {
            if (feature.get('type') === 'TextBlock')
                textblockFeature = feature;
            if (feature.get('type') === 'TextLine')
                textlineFeature = feature;
        });

        // highlight textblock features
        if (textblockFeature !== this.highlightTextblockFeature) {

            if (this.highlightTextblockFeature) {

                this.highlightLayer.getSource().removeFeature(this.highlightTextblockFeature);

            }

            if (textblockFeature) {

                this.highlightLayer.getSource().addFeature(textblockFeature);

            }

            this.highlightTextblockFeature = textblockFeature;

        }

        // highlight textline features
        if (textlineFeature !== this.highlightTextlineFeature) {

            if (this.highlightTextlineFeature) {

                var oldTargetElem = $('#' + this.highlightTextlineFeature.getId())

                if (oldTargetElem.hasClass('highlight') ) {

                    oldTargetElem.removeClass('highlight');

                    this.highlightLayerTextLine.getSource().removeFeature(this.highlightTextlineFeature);

                }

            }

            if (textlineFeature) {

                var targetElem = $('#' + textlineFeature.getId());

                if (targetElem.length > 0 && !targetElem.hasClass('highlight')) {

                    targetElem.addClass('highlight');

                    $('#tx-dlf-fulltextselection').scrollTo(targetElem, 50);

                    this.highlightLayerTextLine.getSource().addFeature(textlineFeature);
                }

            }

            this.highlightTextlineFeature = textlineFeature;

        }

    }, this);
    
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
    };
    
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
	if (this.fulltextData_.length === 0)  {
		this.fulltextData_ = this.fetchFulltextDataFromServer();
				
		// add features to fulltext layer
		this.textBlockLayer.getSource().addFeatures(this.fulltextData_[0]);
	    this.textLineLayer.getSource().addFeatures(this.fulltextData_[1]);
	    
	    // add first feature of textBlockFeatures to map
	    if (this.fulltextData_[0].length > 0) {

	        this.selectLayer.getSource().addFeature(this.fulltextData_[0][0]);
	        this.clickedFeature = this.fulltextData_[0][0];
	        this.showFulltext(this.fulltextData_[0][0]);

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
        this.map.addLayer(this.highlightLayer);
        this.map.addLayer(this.selectLayer);
        this.map.addLayer(this.highlightLayerTextLine);

        // show fulltext container
        var title = dlfViewerFullTextControl.dic[this.lang][1],
            className = 'fulltext-visible';
        $("#tx-dlf-tools-fulltext").addClass(className)
            .text(title)
            .attr('title', title);

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
    this.map.removeLayer(this.highlightLayer);
    this.map.removeLayer(this.selectLayer);
    this.map.removeLayer(this.highlightLayerTextLine);

    // clear all layers
    this.highlightLayer.getSource().clear();
    this.highlightLayerTextLine.getSource().clear()

    var title = dlfViewerFullTextControl.dic[this.lang][0],
        className = 'fulltext-visible';
    $("#tx-dlf-tools-fulltext").removeClass(className)
        .text(title)
        .attr('title', title);

    $('#tx-dlf-fulltextselection').removeClass(className);
    $('#tx-dlf-fulltextselection').hide();
    $('body').removeClass(className);

};

/**
 * Method fetches the fulltext data from the server
 * @param {string} url
 * @return {Array.<Array.<ol.Feature>>} [textBlockFeatures, textLineFeatures]
 */
dlfViewerFullTextControl.prototype.fetchFulltextDataFromServer = function(){

	// fetch data from server
    var request = $.ajax({
        url: this.url,
        async: false
    });

    // parse alto data
    var format = new ol.format.ALTO(),
    	fulltextCoordinates = request.responseXML ? format.readFeatures(request.responseXML) : [];

    // group fulltext coordinates in TextBlock and TextLine features
    // get the Page or PrintSpace feature
	var pageOrPrintSpaceFeature = fulltextCoordinates[0],
		width = pageOrPrintSpaceFeature.get('width'),
		height = pageOrPrintSpaceFeature.get('height');
		
	// group data in TextBlock and TextLine features
	var textBlockFeatures = dlfUtils.scaleToImageSize(pageOrPrintSpaceFeature.get('features'), this.image, 
		width , height),
		textLineFeatures = [];
	for (var j in textBlockFeatures) {
		// add textline coordinates
		textLineFeatures = textLineFeatures.concat(dlfUtils.scaleToImageSize(textBlockFeatures[j].get('textline'),
			this.image, width, height));
	}
	
	return [textBlockFeatures, textLineFeatures];
};

/**
 * Activate Fulltext Features
 *
 * @param {ol.Feature} feature
 */
dlfViewerFullTextControl.prototype.showFulltext = function(feature) {

    var textlines = feature.get('textline'),
        popupHTML = '';

    for (var i = 0; i < textlines.length; i++) {

        // split in case of line break
        var fulltexts = textlines[i].get('fulltext').split('\n'),
            popupHTML = popupHTML + '<span class="textline" id="' + textlines[i].getId() + '">'
                + fulltexts[0].replace(/\n/g, '<br />') + '</span>';


    }

    $('#tx-dlf-fulltextselection').html(popupHTML);

};

/**
 * @const
 * @namespace
 */
dlfViewerFullTextControl.style = {};

/**
 * @return {ol.style.Style}
 */
dlfViewerFullTextControl.style.defaultStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(204,204,204,0.8)',
            'width': 3
        }),
        'fill': new ol.style.Fill({
            'color': 'rgba(170,0,0,0.1)'
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerFullTextControl.style.hoverStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(204,204,204,0.8)',
            'width': 1
        }),
        'fill': new ol.style.Fill({
            'color': 'rgba(238,153,0,0.2)'
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerFullTextControl.style.invisibleStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(170,0,0,0)',
            'width': 1
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerFullTextControl.style.selectStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(170,0,0,0.8)',
            'width': 1
        }),
        'fill': new ol.style.Fill({
            'color': 'rgba(238,153,0,0.2)'
        })
    });

};

/**
 * @return {ol.style.Style}
 */
dlfViewerFullTextControl.style.textlineStyle = function() {

    return new ol.style.Style({
        'stroke': new ol.style.Stroke({
            'color': 'rgba(170,0,0,1)',
            'width': 1
        })
    });

};

/**
 *
 * @type {{en: string[], de: string[]}}
 */
dlfViewerFullTextControl.dic = {
    'en': ['Activate Fulltext', 'Deactivate Fulltext'],
    'de': ['Volltexte an', 'Volltexte aus']
};