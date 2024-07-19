/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
/*global ol */
const className = 'score-visible';
const scrollOffset = 100;
var zoom = 40;
var format = 'mei';
var customOptions = undefined;
var tk = {};
var ids = [];
var pdfTitle = "";

let pdf_blob;

let dlfScoreUtil;
dlfScoreUtil = dlfScoreUtil || {};
const verovioSetting = {
    pageWidth: 300,
    scale: 25,
    //AdjustPageWidth: true,
    spacingLinear: .15,
    pageHeight: 300,
    //AdjustPageHeight: true,
    scaleToPageSize: true,
    breaks: 'encoded',
    mdivAll: true
};

dlfScoreUtil.fetchScoreDataFromServer = function (url, pagebeginning) {
    const result = new $.Deferred();
    tk = new verovio.toolkit();

    if (url === '') {
        result.reject();
        return result;
    }

    $.ajax({url}).done(function (data, status, jqXHR) {
        try {
            let score = tk.renderData(jqXHR.responseText, verovioSettings);
            const pageToShow = tk.getPageWithElement(pagebeginning);
            score = tk.renderToSVG(pageToShow);

            $("#player").midiPlayer({
                onStop: function () { $('body').removeClass('midi-active') }
            });

            $("#tx-dlf-tools-midi").click(
                function () {
                    var base64midi = tk.renderToMIDI();
                    var song = 'data:audio/midi;base64,' + base64midi;

                    $("#player").midiPlayer.play(song);

                    $('body').addClass('midi-active');
                });


            const midi = tk.renderToMIDI();
            const str2blob = new Blob([midi]);

            $("#tx_dlf_mididownload").attr({
                "href": window.URL.createObjectURL(str2blob, {type: "text/plain"}),
                "download": "demo.midi"
            });

            $("#tx_dlf_mididownload").click();

            if (score === undefined) {
                result.reject();
            } else {
                result.resolve(score);
            }


        } catch (e) {
            console.error(e); // eslint-disable-line no-console
            result.reject();
        }
    });

    return [result, tk];
};


/**
 * Encapsulates especially the score behavior
 * @class
 * @param dlfViewer
 * @param pagebeginning
 * @param pagecount
 */
const dlfViewerScoreControl = function (dlfViewer, pagebeginning, pagecount) {

    this.dlfViewer = dlfViewer;

    /**
     *@ type(number)
     */
    this.pagecount = pagecount;

    /**
     * @type {number}
     * @private
     */
    this.position = 0;

    /**
     * @type {string}
     * @private
     */
    this.pagebeginning = pagebeginning;

    /**
     * @type {Object}
     * @private
     */
    this.dic = $('#tx-dlf-tools-score-' + this.dlfViewer.counter).length > 0 && $('#tx-dlf-tools-score-' + this.dlfViewer.counter).attr('data-dic') ?
        dlfUtils.parseDataDic($('#tx-dlf-tools-score-' + this.dlfViewer.counter)) :
        {
            'score': 'Score',
            'score-loading': 'Loading score...',
            'score-on': 'Activate score',
            'score-off': 'Deactivate score',
            'activate-score-initially': '0',
            'score-scroll-element': 'html, body'
        };

    /**
     * @type {number}
     * @private
     */
    this.activateScoreInitially = this.dic['activate-score-initially'] === "1" ? 1 : 0;

    /**
     * @type {string}
     * @private
     */
    this.scoreScrollElement = this.dic['score-scroll-element'];

    $('#tx-dlf-score-' + this.dlfViewer.counter).text(this.dic['score-loading']);

    this.measuresLoaded = false;

    function makeSVG(tag, attrs) {
        var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
        for (var k in attrs)
            el.setAttribute(k, attrs[k]);
        return el;
    }

    this.showMeasures = function() {
        //
        // Draw boxes for each measure
        //
        var dlfViewer = this.dlfViewer;
        var measureCoords = dlfViewer.measureCoords;
        if (!this.measuresLoaded) {
            setTimeout(function() {
                $.each(measureCoords, function (key, value) {

                    var bbox = $('#tx-dlf-score-' + dlfViewer.counter + ' #' + key)[0].getBBox();

                    var measureRect = makeSVG('rect', {
                        x: bbox['x'],
                        y: bbox['y'],
                        width:bbox['width'],
                        height:bbox['height'],
                        stroke: 'none',
                        'stroke-width': 20,
                        fill: 'red',
                        'fill-opacity': '0'
                    });
                    $('#tx-dlf-score-' + dlfViewer.counter + ' #' + key)[0].appendChild(measureRect);

                    if (key === dlfViewer.currentMeasureId) {
                        $($('#tx-dlf-score-' + dlfViewer.counter + ' #' + key + ' > rect')[0]).addClass('active');

                        dlfViewer.verovioMeasureActive = $($('#tx-dlf-score-' + dlfViewer.counter + ' #' + key + ' > rect')[0]);
                    }
                });

                //
                // SVG click event
                //
                $('#tx-dlf-score-' + dlfViewer.counter + ' rect').on('click', function(evt) {
                    // Show ajax spinner if exists
                    if ($('#overlay .ajax-spinner')) {
                        $('#overlay').fadeIn(300);
                    }

                    if (dlfViewer.verovioMeasureActive !== null) {
                        dlfViewer.verovioMeasureActive.removeClass('active');
                        dlfViewer.verovioMeasureActive = null;
                    }
                    if (dlfViewer.facsimileMeasureActive !== null) {
                        dlfViewer.facsimileMeasureActive.setStyle(undefined);
                        dlfViewer.facsimileMeasureActive = null;
                    }

                    dlfViewer.verovioMeasureActive = $(this);
                    // Set measure as active
                    dlfViewer.verovioMeasureActive.addClass('active');
                    var measureId = $(this).parent().attr('id');

                    if (dlfViewer.measureIdLinks[measureId]) {
                        window.location.replace(dlfViewer.measureIdLinks[measureId]);
                    }

                    // Show measure on facsimile
                    if (dlfUtils.exists(dlfViewer.measureLayer)) {
                        dlfViewer.facsimileMeasureActive = dlfViewer.measureLayer.getSource().getFeatureById(measureId);
                        dlfViewer.facsimileMeasureActive.setStyle(dlfViewerOLStyles.selectStyle());
                    }

                });
                //
                // SVG hover event
                //
                $('#tx-dlf-score-' + dlfViewer.counter + ' rect').on('pointermove', function(evt) {
                    if (dlfViewer.verovioMeasureHover !== null) {
                        dlfViewer.verovioMeasureHover.removeClass('hover');
                        dlfViewer.verovioMeasureHover = null;
                    }
                    if (dlfViewer.facsimileMeasureHover !== null && dlfViewer.facsimileMeasureHover !== dlfViewer.facsimileMeasureActive) {
                        dlfViewer.facsimileMeasureHover.setStyle(undefined);
                        dlfViewer.facsimileMeasureHover = null;
                    }

                    dlfViewer.verovioMeasureHover = $(this);
                    // set measure as active
                    dlfViewer.verovioMeasureHover.addClass('hover');
                    var measureId = $(this).parent().attr('id');

                    // Show measure in openlayer
                    if (dlfUtils.exists(dlfViewer.measureLayer)) {
                        dlfViewer.facsimileMeasureHover = dlfViewer.measureLayer.getSource().getFeatureById(measureId);
                        if (dlfViewer.facsimileMeasureHover !== dlfViewer.facsimileMeasureActive) {
                            if (dlfViewer.facsimileMeasureHover) {
                                dlfViewer.facsimileMeasureHover.setStyle(dlfViewerOLStyles.hoverStyle());
                            }
                        }
                    }
                });
                this.measuresLoaded = true;
            }, 1000);
        }
    };



    this.changeActiveBehaviour();
};

function get_pdf_title(tk) {
    var parser = new DOMParser();
    var xmlDoc = parser.parseFromString(tk.getMEI(), "text/xml");
    var work = xmlDoc.getElementsByTagName("work");
    var pdfTitle = work[0].getElementsByTagName("title")[0].textContent;
    return pdfTitle;
}

/**
 * @param {ScoreFeature} scoreData
 */
dlfViewerScoreControl.prototype.loadScoreData = function (scoreData, tk) {
    var target = document.getElementById('tx-dlf-score-' + this.dlfViewer.counter);
    // Const target = document.getElementById('tx-dlf-score');

    var extent = [-2100, -2970, 2100, 2970];
    // [offsetWidth, -imageSourceObj.height, imageSourceObj.width + offsetWidth, 0]

    var proj = new ol.proj.Projection({
        code: 'score-projection',
        units: 'pixels',
        extent: extent
    });

    var map = new ol.Map({
        target: target,
        // View: tx_dlf_viewer.view,
        view: new ol.View({
            projection: proj,
            //Center: [0, 0],            center: ol.extent.getCenter(extent),
            center: [0, 0],
            extent: extent,
            zoom: 1,
            minZoom: 1,
        }),
        interactions: [
            new ol.interaction.DragPan(),
            new ol.interaction.DragZoom(),
            new ol.interaction.PinchZoom(),
            new ol.interaction.MouseWheelZoom(),
            new ol.interaction.KeyboardPan(),
            new ol.interaction.KeyboardZoom(),
            new ol.interaction.DragRotateAndZoom()
        ],
    });
    this.dlfViewer.scoreMap = map;

    var svgContainer = document.createElement('div');
    svgContainer.innerHTML = scoreData;

    const width = 2100;
    const height = 2970;
    svgContainer.style.width = width + 'px';
    svgContainer.style.height = height + 'px';
    svgContainer.style.transformOrigin = 'top left';
    svgContainer.className = 'svg-layer';

    map.addLayer(
        new ol.layer.Layer({
            render: function (frameState) {

                const svgResolution = 1;
                const scale = svgResolution / frameState.viewState.resolution;
                const center = frameState.viewState.center;
                const size = frameState.size;
                const cssTransform = ol.transform.composeCssTransform(
                    size[0] / 2,
                    size[1] / 2,
                    scale,
                    scale,
                    frameState.viewState.rotation,
                    -center[0] / svgResolution - width / 2,
                    center[1] / svgResolution - height / 2
                );

                svgContainer.style.transform = cssTransform;
                svgContainer.style.opacity = this.getOpacity();
                return svgContainer;
            },
        })
    );

    $("#tx_dlf_scoredownload").click(function () {
        if (typeof pdf_blob !== 'undefined') {
            saveAs(pdf_blob, get_pdf_title(tk));

            return;
        }

        var pdfFormat = "A4";
        var pdfOrientation = "portrait";

        var pdfFormat = $("#pdfFormat").val();
        var pdfSize = [2100, 2970];
        if (pdfFormat === "letter") pdfSize = [2159, 2794];
        else if (pdfFormat === "B4") pdfSize = [2500, 3530];

        var pdfOrientation = $("#pdfOrientation").val();
        var pdfLandscape = pdfOrientation === 'landscape';
        var pdfHeight = pdfLandscape ? pdfSize[0] : pdfSize[1];
        var pdfWidth = pdfLandscape ? pdfSize[1] : pdfSize[0];

        var fontCallback = function (family, bold, italic, fontOptions) {
            if (family == "VerovioText") {
                return family;
            }
            if (family.match(/(?:^|,)\s*sans-serif\s*$/) || true) {
                if (bold && italic) {
                    return 'Times-BoldItalic';
                }
                if (bold && !italic) {
                    return 'Times-Bold';
                }
                if (!bold && italic) {
                    return 'Times-Italic';
                }
                if (!bold && !italic) {
                    return 'Times-Roman';
                }
            }
        };

        var options = {};
        options.fontCallback = fontCallback;


        var doc = new PDFDocument({useCSS: true, compress: true, autoFirstPage: false, layout: pdfOrientation});
        var stream = doc.pipe(blobStream());

        stream.on('finish', function () {
            pdf_blob = stream.toBlob('application/pdf');
            saveAs(pdf_blob, get_pdf_title(tk));
        });


        var pdfOptions = {
            adjustPageHeight: false,
            adjustPageWidth: false,
            breaks: "auto",
            mmOutput: true,
            footer: "auto",
            pageHeight: pdfHeight,
            pageWidth: pdfWidth,
            scale: 100
        };

        const pdf_tk = new verovio.toolkit();
        pdf_tk.renderData(tk.getMEI(), pdfOptions);
        var parser = new DOMParser();
        var xmlDoc = parser.parseFromString(tk.getMEI(), "text/xml");
        var work = xmlDoc.getElementsByTagName("work");
        var pdfTitle = work[0].getElementsByTagName("title")[0].textContent;

        for (let i = 0; i < pdf_tk.getPageCount(); i++) {
            doc.addPage({size: pdfFormat, layout: pdfOrientation});
            SVGtoPDF(doc, pdf_tk.renderToSVG(i + 1, {}), 0, 0, options);
        }

        doc.end();

    });


};

function calc_page_height() {
    return ($(document).height() - $("#navbar").height() - 4) * 100 / zoom;
}

function calc_page_width() {
    return ($(".row-offcanvas").width()) * 100 / zoom;
}


function set_options(tk) {

    height = calc_page_height();
    width = calc_page_width();


    if (customOptions !== undefined) {
        localStorage['customOptions'] = JSON.stringify(customOptions);
        var mergedOptions = {};
        for (var key in customOptions) mergedOptions[key] = customOptions[key];
        for (var key in options) mergedOptions[key] = options[key];
        options = mergedOptions;
    }


    options = {
        pageWidth: $('#tx-dlf-score-' + this.dlfViewer.counter).width(),
        scale: 25,
        //adjustPageWidth: true,
        spacingLinear: .15,
        pageHeight: $('#tx-dlf-score-' + this.dlfViewer.counter).height(),
        //adjustPageHeight: true,
        scaleToPageSize: true,
        breaks: 'encoded',
        mdivAll: true
    };

    tk.setOptions(options);
}

/**
 * Add active / deactive behavior in case of click on control depending if the full text should be activated initially.
 */
dlfViewerScoreControl.prototype.changeActiveBehaviour = function () {
    if (dlfUtils.getCookie("tx-dlf-pageview-score-select") === 'enabled' && this.pagecount == 1) {
        this.addActiveBehaviourForSwitchOn();
    } else {
        this.addActiveBehaviourForSwitchOff();
        this.disableScoreSelect();
    }
};

dlfViewerScoreControl.prototype.addActiveBehaviourForSwitchOn = function () {
    const anchorEl = $('#tx-dlf-tools-score-' + this.dlfViewer.counter);
    if (anchorEl.length > 0) {
        const toggleScore = $.proxy(function (event) {
            event.preventDefault();

            this.activateScoreInitially = 0;

            if ($(event.target).hasClass('active')) {
                this.deactivate();
                return;
            }

            this.activate();
        }, this);

        anchorEl.on('click', toggleScore);
        anchorEl.on('touchstart', toggleScore);
    }

    // set initial title of score element
    $('#tx-dlf-tools-score-' + this.dlfViewer.counter)
        .text(this.dic['score'])
        .attr('title', this.dic['score']);

    this.activate();
};

dlfViewerScoreControl.prototype.addActiveBehaviourForSwitchOff = function () {
    const anchorEl = $('#tx-dlf-tools-score-' + this.dlfViewer.counter);
    if (anchorEl.length > 0) {
        const toggleScore = $.proxy(function (event) {
            event.preventDefault();

            if ($(event.target).hasClass('active')) {
                this.deactivate();
                return;
            }

            this.activate();
        }, this);

        anchorEl.on('click', toggleScore);
        anchorEl.on('touchstart', toggleScore);
    }

    // set initial title of score element
    $('#tx-dlf-tools-score-' + this.dlfViewer.counter)
        .text(this.dic['score-on'])
        .attr('title', this.dic['score-on']);

    // if score is activated via cookie than run activation method
    if (dlfUtils.getCookie("tx-dlf-pageview-score-select") === 'enabled') {
        // activate the score behavior
        this.activate();
    }
};

/**
 * Activate Score Features
 */
dlfViewerScoreControl.prototype.activate = function () {
    const controlEl = $('#tx-dlf-tools-score-' + this.dlfViewer.counter);

    // now activate the score overlay and map behavior
    this.enableScoreSelect();
    dlfUtils.setCookie("tx-dlf-pageview-score-select", 'enabled');
    $(controlEl).addClass('active');

    // trigger event
    $(this).trigger("activate-fulltext", this);
};

/**
 * Activate Fulltext Features
 */
dlfViewerScoreControl.prototype.deactivate = function () {
    const controlEl = $('#tx-dlf-tools-score-' + this.dlfViewer.counter);

    // deactivate fulltext
    this.disableScoreSelect();
    dlfUtils.setCookie("tx-dlf-pageview-score-select", 'disabled');
    $(controlEl).removeClass('active');

    // trigger event
    $(this).trigger("deactivate-fulltext", this);
};

/**
 * Disable Score Features
 *
 * @return void
 */
dlfViewerScoreControl.prototype.disableScoreSelect = function () {

    // Resize viewer back to 100% width and remove custom zoom control
    $('#tx-dfgviewer-map-' + this.dlfViewer.counter).width('100%').find('.custom-zoom').hide();
    this.dlfViewer.updateLayerSize();

    // Remove sync button from the view functions in the upper right corner
    $('.view-functions ul li.sync-view').hide();
    // this.dlfViewer.syncControl.unsetSync();

    $('#tx-dlf-tools-score-' + this.dlfViewer.counter).removeClass(className);

    if (this.activateFullTextInitially === 0) {
        $('#tx-dlf-tools-score-' + this.dlfViewer.counter)
            .text(this.dic['score-on'])
            .attr('title', this.dic['score-on']);
    }

    $('#tx-dlf-score-' + this.dlfViewer.counter).removeClass(className).hide();
    $('#tx-dfgviewer-map-' + this.dlfViewer.counter + ' .ol-overlaycontainer-stopevent').hide();
    $('#tx-dfgviewer-map-' + this.dlfViewer.counter + ' ~ .score-tool #tx-dlf-tools-midi').hide();
    $('.document-view:not(.multiview) .document-functions #tx-dlf-tools-midi').hide();


    $('body').removeClass(className);

    if (this.dlfViewer.measureLayer) {
        this.dlfViewer.measureLayer.setVisible(false);
    }

};

/**
 * Activate Score Features
 */
dlfViewerScoreControl.prototype.enableScoreSelect = function () {

    // Resize viewer to 50% width and add custom zoom control
    const customZoom = '<div class="custom-zoom">' + $('.view-functions ul li.zoom').html() + '</div>';
    $('#tx-dfgviewer-map-' + this.dlfViewer.counter).width('50%').append(customZoom);
    this.dlfViewer.updateLayerSize();

    // Add button to sync views to the view functions in the upper right corner
    const syncZoomTitle = $('html[lang^="en"]')[0] ? 'Syncronize zoom function' : 'Zoom-Funktion synchronisieren';
    $('.view-functions ul').append('<li class="sync-view"><a class="sync-view-toggle" title="' + syncZoomTitle + '" onclick="dlfViewerCustomViewSync(this)">' + syncZoomTitle + '</></li>');

    // show score container
    $('#tx-dlf-tools-score-' + this.dlfViewer.counter).addClass(className);

    if (this.activateFullTextInitially === 0) {
        $('#tx-dlf-tools-score-' + this.dlfViewer.counter)
            .text(this.dic['score-off'])
            .attr('title', this.dic['score-off']);
    }

    $('#tx-dlf-score-' + this.dlfViewer.counter).addClass(className).show();

    $('body').addClass(className);
    this.scrollToPagebeginning();

    if (this.dlfViewer.measureLayer) {
        this.dlfViewer.measureLayer.setVisible(true);
    }
    this.showMeasures();
};

/**
 * Scroll to Element with given ID
 */
dlfViewerScoreControl.prototype.scrollToPagebeginning = function () {
    // get current position of pb element
    if (this.pagebeginning) {
        const currentPosition = $('#tx-dlf-score-' + this.dlfViewer.counter + ' svg g#' + this.pagebeginning)?.parent()?.position()?.top ?? 0;
        // set target position if zero
        this.position = this.position == 0 ? currentPosition : this.position;
        // trigger scroll
        $('#tx-dlf-score-' + this.dlfViewer.counter).scrollTop(this.position - scrollOffset);
    } else {
        $('#tx-dlf-tools-score').hide();
    }
};

/**
 * Custom toggle for sync function outside the OpenLayer object
 */
dlfViewerCustomViewSync = function (element) {
    const isActive = $(element).toggleClass('active').hasClass('active');
    isActive ? tx_dlf_viewer.syncControl.setSync() : tx_dlf_viewer.syncControl.unsetSync();
};
