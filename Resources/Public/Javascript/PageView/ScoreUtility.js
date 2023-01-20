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
  //scale: 25,
	//adjustPageWidth: true,
	//spacingLinear: .15,
	//adjustPageHeight: true,
  //scaleToPageSize: true,
  breaks: 'encoded',
  mdivAll: true
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
