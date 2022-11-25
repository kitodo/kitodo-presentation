/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

const className = 'score-visible';
const scrollOffset = 100;
var  zoom = 40;
var format = 'mei';
var customOptions = undefined;
var tk = {}


let dlfScoreUtil;
dlfScoreUtil = dlfScoreUtil || {};
const verovioSetting = {
	pageWidth: $('#tx-dlf-score').width(),
  scale: 25,
	//adjustPageWidth: true,
	spacingLinear: .15,
	pageHeight: $('#tx-dlf-score').height(),
	//adjustPageHeight: true,
  scaleToPageSize: true,
  breaks: 'encoded',
  mdivAll: true
};

dlfScoreUtil.fetchScoreDataFromServer = function(url, pagebeginning) {
    console.log("fetch score data from server ");
    const result = new $.Deferred();
		const tk = new verovio.toolkit();


	if (url === '') {
		result.reject();
		return result;
	}


    $.ajax({ url }).done(function (data, status, jqXHR) {
        try {
            const score = tk.renderData(jqXHR.responseText, verovioSettings);
            const pageToShow = tk.getPageWithElement(pagebeginning);
            console.log('pageToShow: ' + pageToShow);



            console.log(dlfScoreUtils.get_play_midi);
						dlfScoreUtils.get_play_midi(tk);
            const midi = tk.renderToMIDI();
            const str2blob = new Blob([midi]);


            $("#tx_dlf_mididownload").attr({
              "href": window.URL.createObjectURL(str2blob, {type: "text/plain"}),
              "download": "demo.midi"
            });
            $("#tx_dlf_mididownload").click();
            //$("#tx-dlf-tools-midi").click(dlfScoreUtils.get_play_midi(tk));

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
 * @constructor
 * @param {ol.Map} map
 */
const dlfViewerScoreControl = function(dlfViewer, pagebeginning, pagecount) {

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
    this.dic = $('#tx-dlf-tools-score').length > 0 && $('#tx-dlf-tools-score').attr('data-dic') ?
        dlfUtils.parseDataDic($('#tx-dlf-tools-score')) :
        {
            'score':'Score',
            'score-loading':'Loading score...',
            'score-on':'Activate score',
            'score-off':'Deactivate score',
            'activate-score-initially':'0',
            'score-scroll-element':'html, body'};

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

    $('#tx-dlf-score').text(this.dic['score-loading']);

    this.changeActiveBehaviour();
};

/**
 * @param {ScoreFeature} scoreData
 */
dlfViewerScoreControl.prototype.loadScoreData = function (scoreData, tk) {
	const target = document.getElementById('tx-dlf-score');
  if (target !== null) {
		target.innerHTML = scoreData;
	}

  console.log("the doc is ", $("#tx_dlf_scoredownload"));

 $("#tx_dlf_scoredownload").click( function() {
   console.log("this is a download button")
     var pdfFormat = "A4";
   var pdfOrientation = "portrait";

   var pdfFormat = $("#pdfFormat").val();
   var pdfSize = [2100, 2970];
   if (pdfFormat == "letter") pdfSize = [2159, 2794];
   else if (pdfFormat == "B4") pdfSize = [2500, 3530];

   var pdfOrientation = $("#pdfOrientation").val();
   var pdfLandscape = pdfOrientation == 'landscape';
   var pdfHeight = pdfLandscape ? pdfSize[0] : pdfSize[1];
   var pdfWidth = pdfLandscape ? pdfSize[1] : pdfSize[0];

   var fontCallback = function(family, bold, italic, fontOptions) {
       if (family == "VerovioText") {
           return family;
       }
       if (family.match(/(?:^|,)\s*sans-serif\s*$/) || true) {
           if (bold && italic) {return 'Times-BoldItalic';}
           if (bold && !italic) {return 'Times-Bold';}
           if (!bold && italic) {return 'Times-Italic';}
           if (!bold && !italic) {return 'Times-Roman';}
       }
   };

   var options = {};
   options.fontCallback = fontCallback;


   var doc = new PDFDocument({useCSS: true, compress: true, autoFirstPage: false, layout: pdfOrientation});
   var stream = doc.pipe(blobStream());

   stream.on('finish', function() {
       var blob = stream.toBlob('application/pdf');
       var pdfFilename = 'test.pdf'
       saveAs(blob, pdfFilename);
   });


   pdfOptions = {
                       adjustPageHeight: false,
                       adjustPageWidth: false,
                       breaks: "auto",
                       mmOutput: true,
                       footer: "auto",
                       pageHeight: pdfHeight,
                       pageWidth: pdfWidth,
                       scale: 100
           }

   console.log('Setting PDF options');
   console.log(tk);
   tk.setOptions(pdfOptions);
   console.log('Redo layout');
   tk.redoLayout({ "resetCache": false });
   console.log('PDF generation started');
   console.log(tk);
   for (i = 0; i < tk.getPageCount(); i++) {
       doc.addPage({size: pdfFormat, layout: pdfOrientation});
       SVGtoPDF(doc, tk.renderToSVG(i + 1, {}), 0, 0, options);
   }
   tk.redoLayout({ "resetCache": false });
   console.log('PDF generation finished');

   doc.end();

       });


};
function calc_page_height() {
    return ($(document).height() - $( "#navbar" ).height() - 4) * 100 / zoom;
}
function calc_page_width() {
    return ($(".row-offcanvas").width()) * 100 / zoom ; // - $( "#sidbar" ).width();
}


function set_options(tk ) {

    height = calc_page_height();
    width = calc_page_width();


    if (customOptions !== undefined) {
        localStorage['customOptions'] = JSON.stringify(customOptions);
        var mergedOptions = {};
        for(var key in customOptions) mergedOptions[key] = customOptions[key];
        for(var key in options) mergedOptions[key] = options[key];
        options = mergedOptions;
    }


options = {
	pageWidth: $('#tx-dlf-score').width(),
  scale: 25,
	//adjustPageWidth: true,
	spacingLinear: .15,
	pageHeight: $('#tx-dlf-score').height(),
	//adjustPageHeight: true,
  scaleToPageSize: true,
  breaks: 'encoded',
  mdivAll: true
};

console.log($('#tx-dlf-score').width());

    //console.log( options );
    tk.setOptions( options );
    //vrvToolkit.setOptions( mergedOptions );
}
/**
 * Add active / deactive behavior in case of click on control depending if the full text should be activated initially.
 */
dlfViewerScoreControl.prototype.changeActiveBehaviour = function() {
    if (dlfUtils.getCookie("tx-dlf-pageview-score-select") === 'enabled' && this.pagecount == 1) {
        this.addActiveBehaviourForSwitchOn();
    } else  {
        this.addActiveBehaviourForSwitchOff();
        this.disableScoreSelect();
    }
};

dlfViewerScoreControl.prototype.addActiveBehaviourForSwitchOn = function() {
  console.log("addActiveBehaviourForSwitchOn")
    const anchorEl = $('#tx-dlf-tools-score');
    if (anchorEl.length > 0){
        const toggleScore = $.proxy(function(event) {
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
    $("#tx-dlf-tools-score")
        .text(this.dic['score'])
        .attr('title', this.dic['score']);

    this.activate();
};

dlfViewerScoreControl.prototype.addActiveBehaviourForSwitchOff = function() {
  console.log("addActiveBehaviourForSwitchOff")
    const anchorEl = $('#tx-dlf-tools-score');
    if (anchorEl.length > 0){
        const toggleScore = $.proxy(function(event) {
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
    $("#tx-dlf-tools-score")
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
dlfViewerScoreControl.prototype.activate = function() {
  console.log("activate")
    const controlEl = $('#tx-dlf-tools-score');

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
dlfViewerScoreControl.prototype.deactivate = function() {
  console.log("deactivate")
    const controlEl = $('#tx-dlf-tools-score');

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
dlfViewerScoreControl.prototype.disableScoreSelect = function() {
  console.log("disable ScoreSelect  is selcted")

  $('#tx-dfgviewer-map').width('100%');
  this.dlfViewer.updateLayerSize();

    $("#tx-dlf-tools-score").removeClass(className)

    if(this.activateFullTextInitially === 0) {
        $("#tx-dlf-tools-score")
			.text(this.dic['score-on'])
			.attr('title', this.dic['score-on']);
    }

    $('#tx-dlf-score').removeClass(className);
    $('#tx-dlf-score').hide();
    $('body').removeClass(className);

};

/**
 * Activate Score Features
 */
dlfViewerScoreControl.prototype.enableScoreSelect = function() {
  console.log("enable score is selcted")

  $('#tx-dfgviewer-map').width('50%');
  this.dlfViewer.updateLayerSize();


    // show score container
    $("#tx-dlf-tools-score").addClass(className);

    if(this.activateFullTextInitially=== 0) {
        $("#tx-dlf-tools-score")
        .text(this.dic['score-off'])
        .attr('title', this.dic['score-off']);
    }

    $('#tx-dlf-score').addClass(className);
    $('#tx-dlf-score').show();
    $('body').addClass(className);
	this.scrollToPagebeginning();
};

/**
 * Scroll to Element with given ID
 */
dlfViewerScoreControl.prototype.scrollToPagebeginning = function() {
	// get current position of pb element
  if(this.pagebeginning){
    const currentPosition = $('#tx-dlf-score svg g#' + this.pagebeginning)?.parent()?.position()?.top ?? 0;
    // set target position if zero
    this.position = this.position == 0 ? currentPosition : this.position;
    // trigger scroll
    $('#tx-dlf-score').scrollTop(this.position - scrollOffset);
  }else{
        $('#tx-dlf-tools-score').hide();
  }
};
