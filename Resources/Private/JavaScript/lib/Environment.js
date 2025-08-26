// @ts-check

import IntlMessageFormat from 'intl-messageformat';

import { dataUrlMime, getFullscreenElement } from 'lib/util';

/**
 * @typedef {{
 *  twoLetterIsoCode: string;
 *  phrasesInput: PhrasesDict;
 *  phrasesCompiled: Record<string, IntlMessageFormat>;
 * }} Lang
 */

/**
 * Encapsulates various global state and access to browser capabilities.
 * Construct an instance of this at the root of the app and inject / pass it
 * down to the places where it is needed.
 *
 * This allows us, for example, to use fresh `mkid` counters in test cases
 * and to mock browser capabilities if necessary.
 *
 * @implements {Browser}
 * @implements {Identifier}
 * @implements {Translator}
 */
export default class Environment {
  constructor() {
    /**
     * @private
     * @type {number}
     */
    this.idCnt = 0;

    /**
     * @private
     * @type {Partial<HTMLElementTagNameMap>}
     */
    this.testElements = {};

    /**
     * @private
     * @type {Lang}
     */
    this.lang = {
      twoLetterIsoCode: 'en',
      phrasesInput: {},
      phrasesCompiled: {},
    };
  }

  /**
   * @inheritdoc
   * @returns {URL}
   */
  getLocation() {
    return new URL(window.location.href);
  }

  /**
   * @inheritdoc
   * @returns {boolean}
   */
  supportsMediaSource() {
    return (
      window.MediaSource !== undefined // eslint-disable-line compat/compat
      && window.MediaSource.isTypeSupported !== undefined // eslint-disable-line compat/compat
    );
  }

  /**
   * @inheritdoc
   * @param {string} mimeType
   * @returns {boolean}
   */
  supportsCanvasExport(mimeType) {
    const dataUrl = this.getTestElement('canvas').toDataURL(mimeType);
    const actualMime = dataUrlMime(dataUrl);
    return actualMime === mimeType;
  }

  /**
   * @inheritdoc
   * @param {string} mimeType
   * @returns {boolean}
   */
  supportsVideoMime(mimeType) {
    return this.getTestElement('video').canPlayType(mimeType) !== '';
  }

  /**
   * @inheritdoc
   * @return {'ibm' | 'mac'}
   */
  getKeyboardVariant() {
    return navigator.userAgent.includes('Mac') ? 'mac' : 'ibm';
  }

  /**
   * @inheritdoc
   * @returns {boolean}
   */
  isInFullScreen() {
    return getFullscreenElement() !== null;
  }

  /**
   * Mostly taken from Shaka player (shaka.ui.Controls).
   *
   * @inheritdoc
   * @param {HTMLElement} fullscreenElement
   * @param {boolean} forceLandscape
   */
  async toggleFullScreen(fullscreenElement, forceLandscape) {
    if (document.fullscreenElement) {
      if (screen.orientation) {
        screen.orientation.unlock();
      }
      await document.exitFullscreen();
    } else {
      // If we are in PiP mode, leave PiP mode first.
      try {
        if (document.pictureInPictureElement) {
          await document.exitPictureInPicture();
        }
        await fullscreenElement.requestFullscreen({ navigationUI: 'hide' });
        if (forceLandscape && screen.orientation) {
          try {
            // Locking to 'landscape' should let it be either
            // 'landscape-primary' or 'landscape-secondary' as appropriate.

            // typescript issue - see: https://github.com/microsoft/TypeScript-DOM-lib-generator/issues/1615
            // @ts-ignore
            await screen.orientation.lock('landscape');
          } catch (error) {
            // If screen.orientation.lock does not work on a device, it will
            // be rejected with an error. Suppress that error.
          }
        }
      } catch (e) {
        // TODO: Error handling
        console.log(e);
      }
    }
  }

  /**
   * @inheritdoc
   * @returns {string}
   */
  mkid() {
    return `__autoid_${++this.idCnt}`;
  }

  /**
   * @inheritdoc
   * @returns {string}
   */
  uuidv4() {
    // Adopted from https://stackoverflow.com/a/2117523
    return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, (cs) => {
      const c = Number(cs);
      const digit = /** @type {number} */(
        crypto.getRandomValues(new Uint8Array(1))[0]
      );
      return (c ^ (digit & (15 >> (c / 4)))).toString(16);
    });
  }

  /**
   * Set locale and phrases for subsequent calls to {@link t}.
   *
   * Translation phrases should use the ICU MessageFormat syntax for
   * interpolation and pluralization.
   *
   * @param {LangDef} lang
   */
  setLang(lang) {
    this.lang = {
      twoLetterIsoCode: lang.twoLetterIsoCode,
      phrasesInput: lang.phrases,
      phrasesCompiled: {},
    };
  }

  /**
   * Get translated phrase of given {@link key}, using locale and phrases that
   * have been provided by the latest call to {@link setLang}.
   *
   * @param {string} key
   * @param {Record<string, string | number>} values
   * @param {(() => string) | undefined} fallback (Optional) Function to
   * generate fallback string when {@link key} is not fonud.
   * @returns {string}
   */
  t(key, values = {}, fallback = undefined) {
    let phrase = this.lang.phrasesCompiled[key];

    if (phrase === undefined) {
      const phraseStr = this.lang.phrasesInput[key];

      if (phraseStr === undefined) {
        if (typeof fallback === 'function') {
          return fallback();
        } else {
          console.error(`Warning: Translation key '${key}' not defined, fallback not provided.`);
          return key;
        }
      }

      phrase
        = this.lang.phrasesCompiled[key]
        = new IntlMessageFormat(phraseStr, this.lang.twoLetterIsoCode, {}, {
          // Treat HTML as plain text
          ignoreTag: true,
        });
    }

    return /** @type {string} */(phrase.format(values));
  }

  /**
   * @private
   * @template {keyof HTMLElementTagNameMap} K
   * @param {K} tagName
   * @returns {HTMLElementTagNameMap[K]}
   */
  getTestElement(tagName) {
    return this.testElements[tagName] ?? document.createElement(tagName);
  }
}
