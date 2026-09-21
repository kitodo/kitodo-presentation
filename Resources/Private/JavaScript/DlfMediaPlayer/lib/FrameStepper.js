// @ts-check

/**
 * Steps the video element one frame at a time and reports the current frame
 * number.
 *
 * Replaces the vendored VideoFrame library (Allen Sarkisyan, MIT), of which
 * only the frame stepping and the frame counter were used.
 */
export default class FrameStepper {
  /**
   * @param {{id: string, frameRate: number}} options
   */
  constructor(options) {
    /** @type {HTMLVideoElement} */
    this.video = /** @type {HTMLVideoElement} */ (document.getElementById(options.id));
    this.frameRate = options.frameRate;
  }

  /**
   * Returns the current frame number.
   *
   * @returns {number}
   */
  get() {
    return Math.floor(Number(this.video.currentTime.toFixed(5)) * this.frameRate);
  }

  /**
   * Seeks forward by the given number of frames (default 1).
   *
   * @param {number} [frames]
   */
  seekForward(frames) {
    this.seek(frames || 1);
  }

  /**
   * Seeks backward by the given number of frames (default 1).
   *
   * @param {number} [frames]
   */
  seekBackward(frames) {
    this.seek(-(frames || 1));
  }

  /**
   * @param {number} frames
   */
  seek(frames) {
    if (!this.video.paused) {
      this.video.pause();
    }

    const frame = this.get();
    // The small offset keeps the browser from snapping back to the previous
    // frame when seeking forward.
    this.video.currentTime = (frame + frames) / this.frameRate + 0.00001;
  }
}
