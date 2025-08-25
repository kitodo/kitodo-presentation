// @ts-check

import { fillPlaceholders, zeroPad } from 'lib/util';

/**
 * Split total number of seconds into parts (hours, minutes, etc.), then key and
 * format them as suitable to fill timecode placeholders.
 *
 * @param {number} totalSeconds Total number of seconds to be formatted.
 * @param {number | null} fps (Optional) Number of FPS used to calculate frame count.
 */
export function getTimeStringPlaceholders(totalSeconds, fps = null) {
  const parts = getTimeStringParts(totalSeconds, fps ?? 0);

  return {
    h: `${parts.hours}`,
    hh: zeroPad(parts.hours, 2),
    m: `${parts.totalMinutes}`,
    mm: zeroPad(parts.minutes, 2),
    ss: zeroPad(parts.seconds, 2),
    ff: zeroPad(parts.frames, 2),
    '00': zeroPad(Math.floor(parts.fractional * 100), 2),
    '000': zeroPad(Math.floor(parts.fractional * 1000), 3),
  };
}

/**
 * Split total number of seconds into parts (hours, minutes, etc.).
 *
 * @param {number} totalSeconds
 * @param {number} fps
 * @returns {Record<'hours' | 'minutes' | 'totalMinutes' | 'seconds' | 'fractional' | 'frames', number>}
 */
export function getTimeStringParts(totalSeconds, fps = 0) {
  const hours = Math.floor(totalSeconds / 3600);
  const minutes = Math.floor((totalSeconds / 60) % 60);
  const totalMinutes = hours * 60 + minutes;
  const seconds = Math.floor(totalSeconds % 60);
  const fractional = totalSeconds % 1;
  const frames = Math.floor(fractional * fps);

  return { hours, minutes, totalMinutes, seconds, fractional, frames };
}

/**
 * Formats {@link totalSeconds} to a time string.
 *
 * The base format is `hh:mm:ss:ff`. Hours and frames are included depending on
 * {@link showHour} and {@link fps}. The first part is not zero-padded.
 *
 * Adopted from shaka.ui.Utils.buildTimeString.
 *
 * @param {number} totalSeconds Total number of seconds to be formatted.
 * @param {boolean} showHour Whether or not to show hours.
 * @param {number | null} fps (Optional) Number of FPS used to calculate frame
 * count.
 * @returns {string}
 */
export default function buildTimeString(totalSeconds, showHour, fps = null) {
  let template = showHour ? "{h}:{mm}:{ss}" : "{m}:{ss}";
  if (fps) {
    template += ":{ff}";

    if (!showHour) {
      template += "f";
    }
  } else {
    template += ".{00}";
  }

  return fillPlaceholders(template, getTimeStringPlaceholders(totalSeconds, fps));
}
