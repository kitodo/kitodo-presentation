/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Kitodo. Key to digital objects e.V. <contact@kitodo.org>
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
 * @param {Object} settings
 *      {string} parentElId
 *      {string} url
 * @constructor
 */
var dlfAudioPlayer = function (settings) {

    var parentElId = settings.parentElId !== undefined ? settings.parentElId : 'tx-dlf-audio',
      swfPath = settings.swfPath !== undefined ? settings.swfPath : undefined,
      solutions = swfPath !== undefined ? 'html, flash' : 'html',
      audioOptions = settings.audio !== undefined ? settings.audio : undefined;

    if (audioOptions === undefined)
        throw new Error('Missing audio configurations.');

    var format = dlfAudioPlayer.JPLAYER_MIMETYPE_FORMAT_MAPPING[audioOptions.mimeType] !== undefined
            ? dlfAudioPlayer.JPLAYER_MIMETYPE_FORMAT_MAPPING[audioOptions.mimeType]
            : 'mp3';
    audioOptions[format] = audioOptions.url;

    //
    // Load params
    //
    var jPlayerOptions = {
        ready: function() {
          $(this).jPlayer('setMedia', audioOptions)
        },
        solution: solutions,
        supplied: format,
        cssSelectorAncestor: '#jp_container_1',
        useStateClassSkin: true,
        autoBlur: false,
        smoothPlayBar: true,
        keyEnabled: true,
        remainingDuration: false,
        toggleDuration: true
    };

    if (swfPath !== undefined) {
        jPlayerOptions['swfPath'] = swfPath;
    }

    //
    // Initialize the audio player
    //

    // player dom
    $('#' + parentElId).after('<div id="jp_container_1" class="jp-audio" role="application" aria-label="media player">' +
      '<div class="jp-type-single"><div class="jp-gui jp-interface"><div class="jp-volume-controls">' +
      '<button class="jp-mute" role="button" tabindex="0">mute</button><button class="jp-volume-max" role="button" tabindex="0">max volume</button>' +
      '<div class="jp-volume-bar"><div class="jp-volume-bar-value"></div></div></div><div class="jp-controls-holder">' +
      '<div class="jp-controls"><button class="jp-play" role="button" tabindex="0">play</button><button class="jp-stop" role="button" tabindex="0">stop</button>' +
      '</div><div class="jp-progress"><div class="jp-seek-bar"><div class="jp-play-bar"></div></div></div><div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div><span class="jp-duration-divider">/</span>' +
      '<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div><div class="jp-toggles"><button class="jp-repeat" role="button" tabindex="0">repeat</button>' +
      '</div></div></div><div class="jp-details"><div class="jp-title" aria-label="title">&nbsp;</div></div><div class="jp-no-solution">' +
      '<span>Update Required</span>To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.' +
      '</div></div></div>');

    // jplayer init
    $('#' + parentElId).jPlayer(jPlayerOptions);
};

/**
 * @type {{MP3: string}}
 */
dlfAudioPlayer.JPLAYER_MIMETYPE_FORMAT_MAPPING = {
    'audio/mpeg': 'mp3',
    'audio/mp4': 'm4a',
    'audio/wav': 'wav'
};
