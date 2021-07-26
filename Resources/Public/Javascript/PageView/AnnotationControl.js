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


if (jQuery.fn.scrollTo === undefined) {
    jQuery.fn.scrollTo = function(elem, speed) {
        var manualOffsetTop = $(elem).parent().height() / 2;
        $(this).animate({
            scrollTop:  $(this).scrollTop() - $(this).offset().top + $(elem).offset().top - manualOffsetTop
        }, speed === undefined ? 1000 : speed);
        return this;
    };
}

function DlfAnnotationControl(map, image, annotationContainers) {

    this.map = map;

    this.image = image;

    this.annotationContainers = annotationContainers.annotationContainers;

    this.canvas = annotationContainers.canvas;

    this.annotationData = undefined;

    this.dic = $('#tx-dlf-tools-annotations').length > 0 && $('#tx-dlf-tools-annotations').attr('data-dic') ?
        dlfUtils.parseDataDic($('#tx-dlf-tools-annotations')) :
        {'annotations-on':'Display Annotations','annotations-off':'Hide Annotations'};

    this.layers_ = {
        annotationList: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.defaultStyle()
        }),
        annotation: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.invisibleStyle()
        }),
        select: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.selectStyle()
        }),
        hoverAnnotationList: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.hoverStyle()
        }),
        hoverAnnotation: new ol.layer.Vector({
            'source': new ol.source.Vector(),
            'style': dlfViewerOL3Styles.textlineStyle()
        }),
    };

    this.handlers = {
        mapClick: $.proxy(function(event){
            var feature = this.map.forEachFeatureAtPixel(event['pixel'], function(feature, layer) {
                if (feature.get('type') === 'annotationList') {
                    return feature;
                }
            });

            if (feature === undefined) {
                this.layers_.select.getSource().clear();
                this.selectedFeature_ = undefined;
                this.showAnnotationText(undefined);
                return;
            }
            if (this.selectedFeature_) {
                // remove old clicks
                this.layers_.select.getSource().removeFeature(this.selectedFeature_);
            }

            if (feature) {
                // remove hover for preventing an adding of styles
                this.layers_.hoverAnnotationList.getSource().clear();
                // add feature
                this.layers_.select.getSource().addFeature(feature);
            }
            this.selectedFeature_ = feature;

            if (dlfUtils.exists(feature)) {
                this.showAnnotationText([feature]);
            }


        }, this),
        mapHover: $.proxy(function(event){
            // hover in case of dragging
            if (event['dragging']) {
                return;
            };

            var hoverSourceAnnotation = this.layers_.hoverAnnotation.getSource(),
                hoverSourceAnnotationList = this.layers_.hoverAnnotationList.getSource(),
                selectSource = this.layers_.select.getSource(),
                map_ = this.map,
                annotationListFeature,
                annotationFeature;

            map_.forEachFeatureAtPixel(event['pixel'], function(feature, layer) {
                if (feature.get('type') === 'annotationList') {
                    annotationListFeature = feature;
                }
                if (feature.get('type') === 'annotation') {
                    annotationFeature = feature;
                }
            });

            // Handle AnnotationList elements
            var activeSelectAnnotationListEl = selectSource.getFeatures().length > 0 ?
                    selectSource.getFeatures()[0] : undefined,
                activeHoverAnnotationListEl = hoverSourceAnnotationList.getFeatures().length > 0 ?
                    hoverSourceAnnotationList.getFeatures()[0] : undefined,
                isFeatureEqualSelectFeature = activeSelectAnnotationListEl !== undefined && annotationListFeature !== undefined &&
                activeSelectAnnotationListEl.getId() === annotationListFeature.getId() ? true : false,
                isFeatureEqualToOldHoverFeature = activeHoverAnnotationListEl !== undefined && annotationListFeature !== undefined &&
                activeHoverAnnotationListEl.getId() === annotationListFeature.getId() ? true : false;

            if (!isFeatureEqualToOldHoverFeature && !isFeatureEqualSelectFeature) {

                // remove old AnnotationList hover features
                hoverSourceAnnotationList.clear();

                if (annotationListFeature) {
                    // add AnnotationList feature to hover
                    hoverSourceAnnotationList.addFeature(annotationListFeature);
                }

            }

            // Handle Annotation elements
            var activeHoverAnnotationListEl = hoverSourceAnnotation.getFeatures().length > 0 ?
                    hoverSourceAnnotation.getFeatures()[0] : undefined,
                isFeatureEqualToOldHoverFeature = activeHoverAnnotationListEl !== undefined && annotationFeature !== undefined &&
                activeHoverAnnotationListEl.getId() === annotationFeature.getId() ? true : false;

            if (!isFeatureEqualToOldHoverFeature) {

                if (activeHoverAnnotationListEl) {

                    // remove highlight effect on annotation view
                    var oldTargetElem = $('#' + activeHoverAnnotationListEl.getId());

                    if (oldTargetElem.hasClass('highlight') ) {
                        oldTargetElem.removeClass('highlight');
                    }

                    // remove old Annotation hover features
                    hoverSourceAnnotation.clear();

                }

                if (annotationFeature) {

                    // add highlight effect to annotation view
                    var targetElem = $('#' + annotationFeature.getId());

                    if (targetElem.length > 0 && !targetElem.hasClass('highlight')) {
                        targetElem.addClass('highlight');
                        $('#tx-dlf-annotationselection').scrollTo(targetElem, 50);
                        hoverSourceAnnotation.addFeature(annotationFeature);
                    }

                }

            }
            }, this)
    };

    var anchorEl = $('#tx-dlf-tools-annotations');
    if (anchorEl.length > 0){
        var toogleAnnotations = $.proxy(function(event) {
                event.preventDefault();

                if ($(event.target).hasClass('active')){
                    this.deactivate();
                    return;
                }

                this.activate();
            }, this);

        anchorEl.on('click', toogleAnnotations);
        anchorEl.on('touchstart', toogleAnnotations);
    }

    this.selectedFeature_ = undefined;

    // set initial title of annotation element
    $("#tx-dlf-tools-annotations")
        .text(this.dic['annotations-on'])
        .attr('title', this.dic['annotations-on']);

    // if annotation is activated via cookie than run activation methode
    if (dlfUtils.getCookie("tx-dlf-pageview-annotation-select") === 'enabled') {
        // activate the annotation behavior
        this.activate(anchorEl);
    }
}

DlfAnnotationControl.prototype.showAnnotationText = function(featuresParam) {
    var features = featuresParam === undefined ? this.annotationData : featuresParam;
    if (features !== undefined) {
        $('#tx-dlf-annotationselection').children().remove();
        for (var i = 0; i < features.length; i++) {
            var feature = features[i],
                annotations = feature.get('annotations'),
                labelEl;
            if (feature.get('label') !== '') {
                labelEl = $('<span class="annotation-list-label"/>');
                labelEl.text(feature.get('label'));
                $('#tx-dlf-annotationselection').append(labelEl);
            }
            for (var j=0; j<annotations.length; j++) {
                var span = $('<span class="annotation" id="' + annotations[j].getId() + '"/>');
                span.text(annotations[j].get('content'));
                $('#tx-dlf-annotationselection').append(span);
                $('#tx-dlf-annotationselection').append(' ');
            }
            $('#tx-dlf-annotationselection').append('<br /><br />');
        }
    }
};

DlfAnnotationControl.prototype.activate = function() {

    var controlEl = $('#tx-dlf-tools-annotations');

    // Fetch annotation lists from server if the method is called for the first time
    if (this.annotationData === undefined)  {
        this.annotationData = this.fetchAnnotationContainersFromServer(this.annotationContainers, this.image, this.canvas);

        if (this.annotationData !== undefined) {

            this.layers_.annotationList.getSource().addFeatures(this.annotationData);
            for (var dataIndex = 0; dataIndex < this.annotationData.length; dataIndex++) {
                this.layers_.annotation.getSource().addFeatures(this.annotationData[dataIndex].getAnnotations());
            }

            if (this.annotationData.length >0)
            {
                this.showAnnotationText(this.annotationData);
            }
        }
    }

    // now activate the annotation overlay and map behavior
    this.enableAnnotationSelect();
    dlfUtils.setCookie("tx-dlf-pageview-annotation-select", 'enabled');
    $(controlEl).addClass('active');

    // trigger event
    $(this).trigger("activate-annotations", this);

};

DlfAnnotationControl.prototype.deactivate = function() {
    var controlEl = $('#tx-dlf-tools-annotations');
    // deactivate annotations
    this.disableAnnotationSelect();
    dlfUtils.setCookie("tx-dlf-pageview-annotation-select", 'disabled');
    $(controlEl).removeClass('active');
    // trigger event
    $(this).trigger("deactivate-annotations", this);
};

DlfAnnotationControl.prototype.disableAnnotationSelect = function() {
    // register event listeners
    this.map.un('click', this.handlers.mapClick);
    this.map.un('pointermove', this.handlers.mapHover);
    // remove layers
    for (var key in this.layers_) {
        if (this.layers_.hasOwnProperty(key)) {
            this.map.removeLayer(this.layers_[String(key)]);
        }
    };
    var className = 'fulltext-visible';
    $("#tx-dlf-tools-annotations").removeClass(className)
        .text(this.dic['annotations-on'])
        .attr('title', this.dic['annotations-on']);

    $('#tx-dlf-annotationselection').removeClass(className);
    $('#tx-dlf-annotationselection').hide();
    $('body').removeClass(className);

};

DlfAnnotationControl.prototype.enableAnnotationSelect = function(textBlockFeatures, textLineFeatures) {
    // register event listeners
    this.map.on('click', this.handlers.mapClick);
    this.map.on('pointermove', this.handlers.mapHover);
    // add layers to map
    for (var key in this.layers_) {
        if (this.layers_.hasOwnProperty(key)) {
            this.map.addLayer(this.layers_[String(key)]);
        }
    }
    // show annotation container
    var className = 'fulltext-visible';
    $("#tx-dlf-tools-annotations").addClass(className)
        .text(this.dic['annotations-off'])
        .attr('title', this.dic['annotations-off']);

    $('#tx-dlf-annotationselection').addClass(className);
    $('#tx-dlf-annotationselection').show();
    $('body').addClass(className);
};

DlfAnnotationControl.prototype.fetchAnnotationContainersFromServer = function(annotationContainers, image, canvas, optOffset) {
    var annotationListData = [],
        parser;
    parser = new DlfIiifAnnotationParser(image, canvas.width, canvas.height, optOffset);
    annotationContainers.forEach(function(annotationList){
        var responseJson;
        var request = $.ajax({
            url: annotationList.uri,
            async: false
        });
        responseJson = request.responseJSON !== null ? request.responseJSON : request.responseText !== null ? $.parseJSON(request.responseText) : null;
        if (responseJson.label === undefined) {
            responseJson.label = annotationList.label;
        }
        annotationListData.push(parser.parseAnnotationList(responseJson, canvas.id));
    });
    return annotationListData;
}
