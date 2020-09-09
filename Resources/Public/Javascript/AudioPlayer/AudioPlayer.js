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
 * @param {Object} settings
 * @constructor
 */
var dlfAudioPlayer = function (settings) {

    var parentElId = settings.parentElId !== undefined ? settings.parentElId : 'tx-dlf-audio',
      swfPath = settings.swfPath !== undefined ? settings.swfPath : undefined,
      solutions = swfPath !== undefined ? 'html, flash' : 'html',
      audioOptions = settings.audio !== undefined ? settings.audio : undefined;

    if (audioOptions === undefined) {
        throw new Error('Missing audio configurations.');
    }

    var format = dlfAudioPlayer.JPLAYER_MIMETYPE_FORMAT_MAPPING[audioOptions.mimeType] !== undefined
            ? dlfAudioPlayer.JPLAYER_MIMETYPE_FORMAT_MAPPING[audioOptions.mimeType]
            : 'mp3';
    audioOptions[String(format)] = audioOptions.url;

    //
    // Load params
    //
    var jPlayerOptions = {
        ready() {
          $(this).jPlayer('setMedia', audioOptions);
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
    // $('#' + parentElId).after('<div id="jp_container_1" class="jp-audio" role="application" aria-label="media player">' +
    //   '<div class="jp-type-single"><div class="jp-gui jp-interface"><div class="jp-volume-controls">' +
    //   '<button class="jp-mute" role="button" tabindex="0">mute</button><button class="jp-volume-max" role="button" tabindex="0">max volume</button>' +
    //   '<div class="jp-volume-bar"><div class="jp-volume-bar-value"></div></div></div><div class="jp-controls-holder">' +
    //   '<div class="jp-controls"><button class="jp-play" role="button" tabindex="0">play</button><button class="jp-stop" role="button" tabindex="0">stop</button>' +
    //   '</div><div class="jp-progress"><div class="jp-seek-bar"><div class="jp-play-bar"></div></div></div><div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div><span class="jp-duration-divider">/</span>' +
    //   '<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div><div class="jp-toggles"><button class="jp-repeat" role="button" tabindex="0">repeat</button>' +
    //   '</div></div></div><div class="jp-details"><div class="jp-title" aria-label="title">&nbsp;</div></div><div class="jp-no-solution">' +
    //   '<span>Update Required</span>To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.' +
    //   '</div></div></div>');

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
