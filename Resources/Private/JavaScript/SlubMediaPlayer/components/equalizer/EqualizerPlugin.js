// @ts-check

import { blobToDataURL, e, filterNonNull } from 'lib/util';
import { parsePreset } from 'SlubMediaPlayer/components/equalizer/preset';
import DlfMediaPlugin from 'DlfMediaPlayer/DlfMediaPlugin';
import Equalizer from 'SlubMediaPlayer/components/equalizer/Equalizer';
import EqualizerView from 'SlubMediaPlayer/components/equalizer/EqualizerView';
import registerMultiIirProcessor from 'SlubMediaPlayer/components/equalizer/MultiIirProcessor.no-babel';

/**
 * @typedef {import('DlfMediaPlayer/DlfMediaPlayer').default} DlfMediaPlayer
 */

/**
 * Custom element / player plugin that can be used to attach the equalizer to
 * an instance of `<dlf-media>`.
 */
export default class EqualizerPlugin extends DlfMediaPlugin {
  constructor() {
    super();

    /** @private @type {dlf.media.eq.FilterPreset[]} */
    this.presets_ = [];

    /** @private @type {string | null} */
    this.defaultPreset_ = null;

    /** @private @type {EqualizerView | null} */
    this.eqView_ = null;

    /** @private */
    this.handlers = {
      onStorePreset: this.onStorePreset.bind(this),
    };

    /** @private */
    /** @private @type {AudioContext | null} */
    // Don't create AudioContext eagerly: some browsers block creation before
    // a user gesture (autoplay policies). Create / resume it on demand in resumeAudioContext().
    this.context = null;

    /** @private @type {(value?: any) => void} */
    this.markAsResumed = (/* value */) => { };

    /** @private @type {HTMLElement | null} */
    this.resumeHintEl_ = null;

    /** @private */
    this.resumedPromise = new Promise((resolve) => { this.markAsResumed = resolve; });
  }

  /** @private */
  removeResumeHint_() {
    if (this.resumeHintEl_ && this.resumeHintEl_.parentNode) {
      this.resumeHintEl_.parentNode.removeChild(this.resumeHintEl_);
    }
    this.resumeHintEl_ = null;
  }

  get view() {
    return this.eqView_;
  }

  /**
   * @override
   * @param {DlfMediaPlayer} player
   */
  async attachToPlayer(player) {
    if (window.location.protocol !== "https:") {
      console.error("Warning: The equalizer will probably fail without HTTPS");
    }

    // Resume audio context (ensures this.context is available)
    await this.resumeAudioContext();

    if (this.context === null) {
      // As a last resort, create one. This should not normally happen because
      // resumeAudioContext creates it, but this guard avoids null deref.
      const AC = (typeof window.AudioContext !== 'undefined')
        ? window.AudioContext
        : /** @type {any} */ (globalThis).webkitAudioContext;
      if (typeof AC === 'undefined') {
        console.error('AudioContext is not supported in this environment');
        return;
      }
      this.context = new AC();
    }

    if (this.context === null) {
      console.error('AudioContext creation failed');
      return;
    }
    /** @type {AudioContext} */
    const ctx = this.context;

    // Load MultiIirProcessor
    const blob = new Blob([`
      ${registerMultiIirProcessor.toString()}
      ${registerMultiIirProcessor.name}();
    `], { type: 'application/javascript; charset=utf-8' });
    // TODO: Object URLs didn't work in Chrome?
    const dataUrl = await blobToDataURL(blob);
    try {
      await ctx.audioWorklet.addModule(dataUrl);
    } catch (err) {
      console.error('Failed to load equalizer audio worklet:', err);
      // Proceed without the worklet — equalizer may still function with fallback code paths.
    }

    // Connect equalizer
    const source = ctx.createMediaElementSource(player.media);
    const eq = new Equalizer(source);
    eq.connect(ctx.destination);

    // Setup view
    this.eqView_ = new EqualizerView(this.env, eq);
    this.eqView_.addEventListener('store_preset', this.handlers.onStorePreset);
    for (const preset of this.presets_) {
      this.eqView_.addPreset(preset);
    }
    for (const [_key, preset] of Object.entries(this.getLocalPresets())) {
      this.eqView_.addPreset(preset);
    }
    if (this.defaultPreset_ !== null) {
      this.eqView_.selectPreset(this.defaultPreset_);
    }
    this.innerHTML = '';
    this.append(this.eqView_.domElement);
    this.eqView_.resize();
  }

  /**
   *
   * @param {any} presetsData
   */
  parsePresets(presetsData) {
    if (!Array.isArray(presetsData)) {
      return;
    }

    const presets = filterNonNull(presetsData.map(parsePreset));
    this.presets_.push(...presets);

    if (this.eqView_ !== null) {
      for (const preset of presets) {
        this.eqView_.addPreset(preset);
      }
    }
  }

  /**
   *
   * @param {string} key
   */
  selectPreset(key) {
    if (this.eqView_ === null) {
      this.defaultPreset_ = key;
    } else {
      this.eqView_.selectPreset(key);
      this.defaultPreset_ = null;
    }
  }

  /**
   * In some browsers, due to autoplay restrictions, the audio context needs to
   * be resumed after the first user gesture.
   *
   * This sets up the UI and waits for the context to be resumed.
   *
   * @private
   */
  async resumeAudioContext() {
    // Do NOT create or resume the AudioContext here synchronously.
    // Instead show a resume UI and create/resume the context inside the user gesture handlers (pointerdown/keydown). 
    // This avoids browsers blocking the action because it's not triggered by a user gesture.

    // If we already have a context and it's running, immediately resolve.
    if (this.context !== null && this.context.state === 'running') {
      this.markAsResumed();
      await this.resumedPromise;
      return;
    }

    // Show resume hint once - accessible button
    if (!this.resumeHintEl_) {
      const btn = e('button', { className: 'dlf-equalizer-resume', type: 'button', ariaLabel: this.env.t('control.sound_tools.equalizer.resume_context') }, [
        this.env.t('control.sound_tools.equalizer.resume_context'),
      ]);
      btn.addEventListener('click', () => createAndResume());
      this.resumeHintEl_ = btn;
      this.append(btn);
    }

    const createAndResume = async () => {
      // Create AudioContext lazily if missing.
      if (this.context === null) {
        const AC = (typeof window.AudioContext !== 'undefined')
          ? window.AudioContext
          : /** @type {any} */ (globalThis).webkitAudioContext;
        if (typeof AC === 'undefined') {
          // Audio not supported -> resolve anyway
          this.markAsResumed();
          return;
        }
        this.context = new AC();
      }

      // Narrow to local non-null variable for the typechecker.
      const ctx = this.context;
      if (ctx) {
        try {
          // Resume the context (allowed because we're inside a user gesture).
          await ctx.resume();
        } catch (e) {
          // ignore
        }
      }
      // remove resume hint if present
      this.removeResumeHint_();
      this.markAsResumed();
    };

    // Attach once-only handlers that will create/resume the context on first user interaction.
    window.addEventListener('pointerdown', createAndResume, { once: true, capture: true });
    window.addEventListener('keydown', createAndResume, { once: true, capture: true });

    // Also attempt to resume if the browser reports a running context later (edge case), but don't create it here.
    if (this.context !== null && this.context.state === 'running') {
      this.removeResumeHint_();
      this.markAsResumed();
    }

    await this.resumedPromise;
  }

  /**
   * @private
   * @param {import('SlubMediaPlayer/components/equalizer/EqualizerView').EqualizerViewEvent<'store_preset'>} event
   */
  onStorePreset(event) {
    const { key, preset } = event.detail;
    localStorage[`dlf.eq.presets.${key}`] = JSON.stringify(preset);
  }

  /**
   * @private
   * @returns {Record<string, dlf.media.eq.FilterPreset>}
   */
  getLocalPresets() {
    /** @type {Record<string, dlf.media.eq.FilterPreset>} */
    const presets = {};
    for (let i = 0; i < localStorage.length; i++) {
      const storageKey = localStorage.key(i);
      if (storageKey === null || !storageKey.startsWith('dlf.eq.presets.')) {
        continue;
      }

      try {
        const preset = parsePreset(JSON.parse(localStorage[storageKey]));

        if (preset !== null) {
          const presetKey = storageKey.substring('dlf.eq.presets.'.length);
          presets[presetKey] = preset;
        }
      } catch (e) {
        //
      }
    }
    return presets;
  }
}

customElements.define('dlf-equalizer', EqualizerPlugin);
