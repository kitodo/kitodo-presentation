=============
Configuration
=============

Placeholders
============

Some configuration values (e.g., screenshot captions) use template strings.
These may contain placeholders that substitute timecode or metadata information.

.. t3-field-list-table::
   :header-rows: 1

   - :Placeholder:
         Placeholder
     :Description:
         Replaced by

   - :Placeholder:
         ``{<metadata>}`` (replace ``<metadata>`` by the index name of a configured metadata entry)
     :Description:
         Metadatum in current document

   - :Placeholder:
         ``{url}``
     :Description:
         Current video URL with timecode information

   - :Placeholder:
         ``{host}``
     :Description:
         Current protocol and hostname (e.g., ``https://sachsen.digital``)

   - :Placeholder:
         ``{h}``
     :Description:
         Hours elapsed in video

   - :Placeholder:
         ``{m}``
     :Description:
         Total minutes elapsed in video

   - :Placeholder:
         ``{hh}``, ``{mm}``, ``{ss}``, ``{ff}``
     :Description:
         Hours, minutes, seconds, frames elapsed (two-digit, leading zero)

   - :Placeholder:
         ``{00}``, ``{000}``
     :Description:
         Fractional part of seconds elapsed (two or three digit, leading zeros)

Commented Example
=================

The following sample explains all base configuration options available from TypoScript.

For information on equalizer configuration, see :ref:`the equalizer subpage <eq_configuration>`.

.. code-block:: typoscript

   plugin.tx_dlf_mediaplayer {
     settings {
       playerTranslations {
         // Language file of player localization strings without language prefix
         baseFile = EXT:dlf/Resources/Private/Language/locallang_media.xlf
       }

       // Share buttons to be shown in bookmark modal
       // Both numeric and non-numeric keys may be used
       shareButtons {
         0 {
           // Type of the button/icon: 'material' or 'image'
           type = material
           // For material icons, this is the icon key (https://material.io/icons)
           icon = email
           // Key in to specified language file shown as tooltip
           titleTranslationKey = share.email.tooltip
           // Template of generated link target. Placeholders may be used.
           hrefTemplate = mailto:?body={url}%0A%0A
         }

         1 {
           type = material
           icon = qr_code
           titleTranslationKey = share.qr_code.tooltip
           // 'dlf:qr_code' indicates that a QR code of the video URL should be shown
           hrefTemplate = dlf:qr_code
         }

         2 {
            type = image
            // For icons based on images, specify the image source
            src = EXT:dlf/Resources/Public/Images/mastodon-logo-purple.svg
            titleTranslationKey = share.mastodon.tooltip
            hrefTemplate = dlf:mastodon_share
         }
       }

       // Captions that can be shown shown on generated screenshots
       screenshotCaptions {
         0 {
           // Horizontal position: left, center, right
           h = left
           // Vertical position / baseline: top, middle, bottom
           v = bottom
           // Text to be shown. Placeholders may be used.
           text = {host}
         }

         1 {
           h = right
           v = bottom
           // This is an example to show metadata
           text = {title}
         }
       }

       constants {
         // Number of seconds in which to still rewind to previous chapter
         prevChapterTolerance = 5

         // Fractional value of volume increase/decrease when pressing up/down arrow keys
         volumeStep = 0.05

         // Number of seconds for seek/rewind
         seekStep = 5

         // On mobile, whether or not to switch to landscape in fullscreen mode
         forceLandscapeOnFullscreen = 1

         // Whether or not showing the Poster Image, if given, until playback is first started
         showPoster = 1

         // Whether or not showing the Audio Label-Image in Audio mode inside the media-panel
         showAudioLabelImage = 1

         // Template of filename used when downloading screenshot (without file extension)
         // Placeholders may be used
         screenshotFilenameTemplate = sachsen-digital-de_{title}_h{hh}m{mm}s{ss}f{ff}

         // Template of comment that is written to screenshot metadata
         // (EXIF in JPEG, iTxt in PNG)
         // Placeholders may be used
         screenshotCommentTemplate (
   Screenshot taken on Sachsen.Digital.

   {url}
   )
       }
     }
   }

Additional TypoScript Setup
===========================

To ensure that the correct templates and view-functions are loaded for the audio/video mediaplayer, an additional TypoScript configuration is required.

First, define variables that indicate whether the current document contains audio or video content. These variables are evaluated dynamically and passed to the plugin:

.. code-block:: typoscript

   page.10.variables {
       isAudio = TEXT
       isAudio.value = 0
       isVideo = TEXT
       isVideo.value = 0
   }

   [isAudio({$plugin.tx_dlf.persistence.storagePid})]
       page.10.variables.isAudio.value = 1
   [END]

   [isVideo({$plugin.tx_dlf.persistence.storagePid})]
       page.10.variables.isVideo.value = 1
   [END]

These conditions ensure that the frontend correctly detects the media type and loads the appropriate templates and functionality for either audio or video playback. 
This Block needs to be above the toolbox configuration. So the variables are defined, before they got copied into the tx_dlf_audiovideotool plugin.

Next, extend the toolbox plugin to register the audiovideotool. This makes the player variables available within the Kitodo toolbox and reuses shared functionality:

.. code-block:: typoscript

   plugin.tx_dlf_audiovideotool < plugin.tx_dlf_toolbox
   plugin.tx_dlf_audiovideotool {
       settings {
           tools = audioVideoTool
           
           # copy constants from Mediaplayer into the plugin
           constants < plugin.tx_dlf_mediaplayer.settings.constants

           # copy variables into the plugin
           isAudio < page.10.variables.isAudio.value
           isVideo < page.10.variables.isVideo.value
       }
   }

The configuration above links the audiovideotool with the existing media player settings and ensures that all constants (e.g., seek steps, captions, and behavior) are reused consistently.
