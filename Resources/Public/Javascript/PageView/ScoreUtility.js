'use strict';

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
 * Base namespace for utility functions used by the dlf module.
 *
 * @const
 */

let dlfScoreUtils;
dlfScoreUtils = dlfScoreUtils || {};
const verovioSettings = {
	pageWidth: $('#tx-dlf-score').width() *4,
  scale: 25,
	adjustPageWidth: true,
	spacingLinear: .15,
	pageHeight: 60000,
	adjustPageHeight: true
};

// dlfScoreUtils.get_play_midi = function (toolkit) {
//   return function (){
//     var base64midi = toolkit.renderToMIDI();
//     var song = 'data:audio/midi;base64,' + base64midi;
//     console.log($("#player").midiplayer)
//     $("#player").midiplayer.play(song);
//   }
// }




/**
 * Method fetches the score data from the server
 * @param {string} url
 * @return {svg}
 * @static
 */
dlfScoreUtils.fetchScoreDataFromServer = function(url) {

    const result = new $.Deferred();
	if (url === '') {
		result.reject();
		return result;
	}

    $.ajax({ url }).done(function (data, status, jqXHR) {
        try {
			const tk = new verovio.toolkit();
            const score = tk.renderData(jqXHR.responseText, verovioSettings);
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

    return result;
};
function generate_pdf() {

      pdfFormat = "A4";
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
          var pdfFilename = outputFilename.replace(/\.[^\.]+$/, '.pdf');
          saveAs(blob, pdfFilename);
      });

      var buffer = Uint8Array.from(atob(vrvTTF), c => c.charCodeAt(0));
      doc.registerFont('VerovioText',buffer);

      pdfOptions = {
                  adjustPageHeight: false,
                  breaks: "auto",
                  mmOutput: true,
                  footer: "auto",
                  pageHeight: pdfHeight,
                  pageWidth: pdfWidth,
                  scale: 100
      }

      vrvToolkit.setOptions(pdfOptions);
      vrvToolkit.redoLayout({ "resetCache": false });
      for (i = 0; i < vrvToolkit.getPageCount(); i++) {
          doc.addPage({size: pdfFormat, layout: pdfOrientation});
          SVGtoPDF(doc, vrvToolkit.renderToSVG(i + 1, {}), 0, 0, options);
      }

      // Reset the options
      set_options();
      vrvToolkit.redoLayout({ "resetCache": false });

      doc.end();
  }
dlfScoreUtils.get_play_midi = function (toolkit) {
  $("#tx-dlf-tools-midi").click(
	function () {
			var base64midi = toolkit.renderToMIDI();
			var song = 'data:audio/midi;base64,' + base64midi;
			$("#player").show();
			$("#tx-dlf-tools-midi").hide();
      $("#player").midiPlayer();
			$("#player").midiPlayer.play(song);
	})
	return dlfScoreUtils
}
