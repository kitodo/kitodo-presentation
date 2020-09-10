//var demoMovieFile = '/typo3conf/ext/dfgviewer/Resources/Public/dummy/content/bbb_sunflower_1080p_30fps_normal.mp4'; // TODO: get source file and metadata from backend / dom
//var demoMovieFile = '/typo3conf/ext/dfgviewer/Resources/Public/dummy/content/vid_dig_x_000622.mp4';
//var demoMovieFile = '/typo3conf/ext/dfgviewer/Resources/Public/dummy/content/2019-3_33_Filmsplitter_von_einer_Konzertreise_Hamburg_1978.mp4';
var demoMovieFile = '';
var fps = 25;
var viewport;

var video;
var timeDisplayModes = [
  $('.time-current'),
  $('.time-remaining'),
  $('.frame-current'),
];
var displayModeIndex = 0;

$(document).ready(function () {
    var isVideo = $(".mime-type-video");
    //demoMovieFile = decodeURI($(".mime-type-video").data('url'));
    demoMovieFile = $(".mime-type-video").data('url');
    if(isVideo && isVideo.length > 0) {
        viewport = $("#mediaplayer-viewport");
        initializePlayer();
        bindPlayerFunctions();
        bindKeyboardEvents();
        resizeVideoCanvas();
        toggleTimeDisplayMode();
    }
});

$(window).resize(function() {
    resizeVideoCanvas();
});

function generateMetadataObject() {
  var dataDomElement = $('#metadata');
  var metadataObject = {};
  metadataObject.metadata = [];
  metadataObject.screenshotFields = dataDomElement.data('screenshotfields').split(',');

  for(var i=0; i < dataDomElement.children().length; i++) {
    if(dataDomElement.children()[i].value.length) {
      metadataObject.metadata[dataDomElement.children()[i].id] = dataDomElement.children()[i].value;
    }
  }
  return metadataObject;
}

function resizeVideoCanvas() {
    var view, player, video;
    view = $('.document-view');
    player = $('.mediaplayer-container');
    video = $("video");
    video.css({
        width: '100%',
        height: 'auto',
    });
    if(player.height() > view.height()) {
        video.css({
            width: '80%',
            height: 'auto',
        });
    }
}

/**
 * binds all necessary video player functions
 */
function bindPlayerFunctions() {

    // binding for settings button
    $('.button-settings').bind('click', function() {
        toggleSettingsMenu();
    });

    // binding for next frame button
    $('.button-nextframe').bind('click', function() {
        frameForward();
    });

    // binding for previous frame button
    $('.button-lastframe').bind('click', function() {
        frameBackward();
    });

    // binding for fast backward button
    $('.button-backward').bind('click', function() {
        backward();
    });

    // binding for fast forward button
    $('.button-forward').bind('click', function () {
        forward();
    });

    $('.time-counters').bind('click', function() {
      toggleTimeDisplayMode();
    });

    // function for binding the settings menu button
    bindSettingsMenuItems();

    // binds the speed options
    bindSpeedSettings();

    // current time and time remaining counter
    viewport.bind($.jPlayer.event.timeupdate, function(event) {
        $(".time-current").text(getFormattedVideoCurrentTime());
        $(".frame-current").text(video.get());
        $(".time-remaining").text('-' + $.jPlayer.convertTime( event.jPlayer.status.duration - event.jPlayer.status.currentTime ));
        resizeVideoCanvas();
    });

    // initialize the counter with correct values after player initialization
    viewport.bind($.jPlayer.event.canplay, function(event) {
        generateChapters();
        $(".time-current").text(getFormattedVideoCurrentTime());
        $(".frame-current").text(video.get());
        $(".time-remaining").text('-' + $.jPlayer.convertTime( event.jPlayer.status.duration - event.jPlayer.status.currentTime ));
    });

    viewport.bind($.jPlayer.event.loadeddata, function(event) {
        if(getParams(document.URL)['timecode']) {
            viewport.jPlayer( "pause", parseFloat(getParams(document.URL)['timecode']) );
        }
        resizeVideoCanvas();
    });
}

/**
 * binds the settings menu items (outsourced for better overview)
 */
function bindSettingsMenuItems() {

    // right click on mediaplayer-viewport for toggle settings menu
    $('#mediaplayer-viewport').contextmenu(function(event) {
        event.preventDefault();
        toggleSettingsMenu();
    });

    // bind back buttons
    $('.menu-item-back').bind('click', function() {
        $('.viewport-menu').children().hide();
        $('.settings-menu').show('fast');
    });

    // bind speed settings
    $('.settings-menu-item-speed-menu').bind('click', function() {
        $('.settings-menu').hide();
        $('.speed-menu').show('fast');
    });

    // bind quality settings
    $('.settings-menu-item-quality-menu').bind('click', function() {
        $('.settings-menu').hide();
        $('.quality-menu').show('fast');
    });

    // bind subtitle settings
    $('.settings-menu-item-subtitle').bind('click', function() {
        $('.settings-menu').hide();
        $('.subtitle-menu').show('fast');
    });

    // bind subtitle settings
    $('.settings-menu-item-language').bind('click', function() {
        $('.settings-menu').hide();
        $('.language-menu').show('fast');
    });

    // binds the help button in settings menu
    $('.settings-menu-item-help').bind('click', function() {
        $('.viewport-menu').hide();
        $('.dfgplayer-help').show('fast');
    });

    $('.settings-menu-item-screenshot').bind('click', function() {
        renderScreenshot();
    });

    $('.settings-menu-item-url').click(function (e) {
        $('.viewport-menu').hide();
        generateUrl();
    });

    // binds the close action from help window to close button
    $('.modal-close').bind('click', function() {
        $('.dfgplayer-help').hide('fast');
    });
}

/**
 * autobinds the possible speed options from dom
 */
function bindSpeedSettings() {
    $('.speed-menu').children().each(function() {
        if($(this).data('speed')) {
            $(this).bind('click', function() {
                viewport.jPlayer('option', 'playbackRate', $(this).data('speed'));
                $('.speed-label').text($(this).data('speed') + 'x');
                $('.viewport-menu').children().hide();
                $('.settings-menu').show();
            });
        }
    });
}

/**
 * binds keyboard events for player keyboard controls
 */
function bindKeyboardEvents() {
 $(document).keydown(function (e) {
     switch (e.keyCode) {
         case 13:
             // toggle Fullscreen (ALT + Return)
             (e.altKey && viewport.data("jPlayer").options.fullScreen) ? viewport.jPlayer("option", "fullScreen", false) : viewport.jPlayer("option", "fullScreen", true);
             break;
         case 32:
             // toggle Play / Pause (Space)
             viewport.data("jPlayer").status.paused ? viewport.jPlayer( "play") : viewport.jPlayer( "pause");
            break;
         case 37:
             // frameskip backward / fast backward (left / shift left)
             (e.shiftKey === true) ? backward() : frameBackward();
             break;
         case 39:
             // frameskip forward / fast forward (right / shift right)
             (e.shiftKey === true) ? forward() : frameForward();
             break;
         case 72:
             // opens help window (key H)
             toggleHelp();
             break;
         case 77:
             // toggle volume - mute (m)
             viewport.data("jPlayer").options.muted ? viewport.jPlayer("option", "muted", false) : viewport.jPlayer("option", "muted", true);
             break;
         case 112:
             // opens help window (key F1)
             e.preventDefault();
             toggleHelp();
             break;
         case 187:
             // Volume Up (+ Key)
             toggleVolumeBar();
             viewport.jPlayer("option", "volume", (viewport.data("jPlayer").options.volume + 0.1));
             break;
         case 189:
             // Volume Down (- Key);
             toggleVolumeBar();
             viewport.jPlayer("option", "volume", (viewport.data("jPlayer").options.volume - 0.1));
             break;
     }
 });
}

/**
 * initializes the jplayer
 */
function initializePlayer() {
    viewport.jPlayer( {
        ready: function() {
            $(this).jPlayer( "setMedia", {
                m4v: demoMovieFile,

            });
        },
        backgroundColor: "#000000",
        supplied: "m4v",
        swfPath: "/typo3conf/ext/dlf/Resources/Public/Javascript/jPlayer/jquery.jplayer.swf",
        size: {
            width: "100%",
            height: "auto"
        },
        cssSelectorAncestor: ".media-viewport",
        cssSelector: {
            videoPlay: ".button-play",
            play: ".button-play",
            pause: ".button-pause",
            stop: ".button-stop",
            seekBar: ".jp-seek-bar",
            playBar: ".jp-play-bar",
            mute: ".button-mute",
            unmute: ".button-unmute",
            volumeBar: ".jp-volume-bar",
            volumeBarValue: ".jp-volume-bar-value",
            volumeMax: ".jp-volume-max",
            playbackRateBar: ".jp-playback-rate-bar",
            playbackRateBarValue: ".jp-playback-rate-bar-value",
            currentTime: ".jp-current-time",
            duration: ".jp-duration",
            title: ".jp-title",
            fullScreen: ".button-fullscreen",
            restoreScreen: ".button-minimize",
            repeat: ".jp-repeat",
            repeatOff: ".jp-repeat-off",
            gui: ".control-bars",
            noSolution: ".jp-no-solution"
        },
    });
    viewport.jPlayer( "load" );

    video = VideoFrame({
        id: 'jp_video_0',
        frameRate: fps,
        callback : function(response) {
            console.log('callback response: ' + response);
        }
    });
}

/**
 * generates timeline markers for chapter selection
 */
function generateChapters() {
    var length = getMediaLength();
    var seekBar = $('.jp-seek-bar');

    $('.chapter').each(function() {
        var timecode = $(this).data('timecode');
        var title = $(this).data('title');
        $('<span />', {
            'class': 'jp-chapter-marker',
            title: $(this).data('title'),
            style: 'position: absolute; left: ' + ((timecode -0.5) * 100 / length) + '%',
            click: function() {
                play(timecode);
            }

        }).appendTo(seekBar);
    });
}

function generateUrl() {
    var $timecodeUrl = document.URL, $urlInput = $('#url-field'), urlContainer = $('#url-container');
    if(getParams($timecodeUrl)) {
        $timecodeUrl = $timecodeUrl + '&timecode=' + viewport.data("jPlayer").status.currentTime;
    } else {
        $timecodeUrl = $timecodeUrl + '?timecode=' + viewport.data("jPlayer").status.currentTime;
    }

    $urlInput.val($timecodeUrl);
    urlContainer.show('fast');
}

/**
 * toggles the media player settings window
 */
function toggleSettingsMenu() {
    var menuContainer = $('.viewport-menu');
    menuContainer.children().hide();
    $('.settings-menu').show();
    menuContainer.toggle('fast');

}

/**
 * returns the length from initialized media file
 * @returns {string|number|string}
 */
function getMediaLength() {
    return viewport.data("jPlayer").status.duration;
}

/**
 * shows next frame
 */
function frameForward() {
    if(viewport.data("jPlayer").status.currentTime < viewport.data("jPlayer").status.duration) {
        video.seekForward(1);
    }
}

/**
 * shows previous frame
 */
function frameBackward() {
    if(viewport.data("jPlayer").status.currentTime > 0) {
        video.seekBackward(1);
    }
}

/**
 * plays the current Position + 10sec
 */
function forward() {
    if((viewport.data("jPlayer").status.currentTime + 10) < viewport.data("jPlayer").status.duration) {
        viewport.jPlayer( "play", viewport.data("jPlayer").status.currentTime + 10 );
    }
}

/**
 * jplays the current Position - 10sec
 */
function backward() {
    if((viewport.data("jPlayer").status.currentTime - 10) > 0) {
        viewport.jPlayer( "play", viewport.data("jPlayer").status.currentTime - 10 );
    }
}
/**
 * plays the media from a individual position in media stream
 * @param seconds
 */
function play(seconds) {
  viewport.jPlayer( "play", seconds );
}

/**
 * opens the help window
 */
function toggleHelp() {
    var helpModal = $('.dfgplayer-help');
    helpModal.css('display') === 'none' ? helpModal.show('fast') : helpModal.hide('fast');
}

function toggleVolumeBar() {
    var volumeBar = $('.jp-volume-bar');
    volumeBar.css({
        visibility: 'visible',
        opacity: 1
    });
    setTimeout(function() {
        volumeBar.css({
            visibility: 'hidden',
            opacity: 0
        });
    }, 3000);
}

function renderScreenshot() {
    toggleSettingsMenu();
    // add canvas overlay to DOM
    var domElement = $( "<div id='screenshot-overlay'><span class='close-screenshot-modal icon-close'></span><canvas id='screenshot-canvas'></canvas></div>" );
    $('body').append(domElement);

    // bind close action
    $('.close-screenshot-modal').bind('click', function() {
       $('#screenshot-overlay').detach();
    });

    // lets go
    drawCanvas();
}

function drawCanvas() {
    var videoDomElement, canvas, context
    var stringArray = [], infoString = '';

    videoDomElement = document.getElementById('jp_video_0');
    canvas = document.getElementById('screenshot-canvas');
    var metadataArray = this.generateMetadataObject();

    for (var i = 0; i < metadataArray.screenshotFields.length; i++) {
      if(typeof(metadataArray.metadata[metadataArray.screenshotFields[i]]) === 'string') {
        stringArray.push(metadataArray.metadata[metadataArray.screenshotFields[i]]);
      }
    }

    for (var i = 0; i < stringArray.length; i++) {
      if((stringArray.length - 1) !== i) {
        infoString += stringArray[i] + ' / ';
      } else {
        infoString += stringArray[i];
      }
    }

    canvas.width = videoDomElement.videoWidth;
    canvas.height = videoDomElement.videoHeight;

    context = canvas.getContext('2d');

    context.drawImage(videoDomElement, 0, 0, canvas.width, canvas.height);

    context.font = '25px Arial';
    context.textAlign = 'end';
    context.fillStyle = "#FFFFFF";
    context.shadowBlur = 5;
    context.shadowColor = "black";
    context.fillText(infoString, canvas.width -10, canvas.height -10);

    canvas.style.width = '80%';
    canvas.style.height = 'auto';
}

function toggleTimeDisplayMode() {
  for (var i=0; i < timeDisplayModes.length; i++) {
    timeDisplayModes[i].css('display', 'none');
  }

  if(displayModeIndex < timeDisplayModes.length) {
    timeDisplayModes[displayModeIndex].css('display', 'block');
    displayModeIndex ++;
  } else {
    displayModeIndex = 0;
    toggleTimeDisplayMode();
  }
}

function getFormattedVideoCurrentTime() {
    var mediaStatus = viewport.data("jPlayer").status;
    return (mediaStatus.currentTime < 3600 ? '00:' : '') + $.jPlayer.convertTime(mediaStatus.currentTime) + ':' + ("0" + (video.get() % fps)).slice(-2);
}

