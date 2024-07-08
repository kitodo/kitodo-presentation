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
    console.log('function working?')
			var base64midi = toolkit.renderToMIDI();
			var song = 'data:audio/midi;base64,' + base64midi;
			// $("#player").show();
			// $("#tx-dlf-tools-midi").hide();
			console.log("this is song " + song);

			$("#player").midiPlayer.play(song);
	})
	return dlfScoreUtils
}


/**
 * Parse from an alto element a OpenLayers geometry object
 * @param {Element} node
 * @return {ol.geom.Polygon|undefined}
 * @private
 */
dlfScoreUtil.parseGeometry_ = function(node) {
    var width = parseInt(node.getAttribute("lrx")) - parseInt(node.getAttribute("ulx")),
        height = parseInt(node.getAttribute("lry")) - parseInt(node.getAttribute("uly")),
        x1 = parseInt(node.getAttribute("ulx")),
        y1 = parseInt(node.getAttribute("uly")),
        x2 = x1 + width,
        y2 = y1 + height,
        coordinatesWithoutScale = [[[x1, -y1], [x2, -y1], [x2, -y2], [x1, -y2], [x1, -y1]]];

    if (isNaN(width) || isNaN(height)) {
        return undefined;
    }

    // return geometry without rescale
    if (!dlfUtils.exists(this.image_) || !dlfUtils.exists(this.width_)) {
        return new ol.geom.Polygon(coordinatesWithoutScale);
    }

    // rescale coordinates
    var scale = this.image_.width / this.width_,
        offset = dlfUtils.exists(this.offset_) ? this.offset_ : 0,
        coordinatesRescale = [];

    for (var i = 0; i < coordinatesWithoutScale[0].length; i++) {
        coordinatesRescale.push([offset + ( scale * coordinatesWithoutScale[0][i][0]),
            0 - (scale * coordinatesWithoutScale[0][i][1])]);
    };

    return new ol.geom.Polygon([coordinatesRescale]);
};

/**
 * Parse from an alto element a OpenLayers feature object ulx, uly, lrx, lry
 * @param {Element} node
 * @return {ol.Feature}
 * @private
 */
dlfScoreUtil.parseFeatureWithGeometry_ = function(node) {
    var geometry = this.parseGeometry_(node),
        width = parseInt(node.getAttribute("lrx")) - parseInt(node.getAttribute("ulx")),
        height = parseInt(node.getAttribute("lry")) - parseInt(node.getAttribute("uly")),
        hpos = parseInt(node.getAttribute("ulx")),
        vpos = parseInt(node.getAttribute("uly")),
        type = node.nodeName.toLowerCase(),
        id = node.getAttribute("xml:id"),
        feature = new ol.Feature(geometry);

    feature.setId(id);
    feature.setProperties({
        type,
        width,
        height,
        hpos,
        vpos
    });

    return feature;
};
