/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/* global dlfUtils */

/**
 * @class
 * @param {string=} pageId
 */
class dlfTeiParser {y
    constructor(pageId) {
      /**
       * @type {string|undefined}
       * @private
       */
      this.pageId = dlfUtils.exists(pageId) ? pageId : undefined;
    }
};

/**
 * @param {XMLDocument|string} document
 * @returns {object}
 */
dlfTeiParser.prototype.parse = function(document) {
    const parsedDoc = this.parseXML(document);

    // Remove all <script> elements
    parsedDoc.querySelectorAll('script').forEach(script => script.remove());

    let contentHtml = $(parsedDoc).find('text')[0].innerHTML;

    // Remove tags but keep their content
    // eslint-disable-next-line
    contentHtml = contentHtml.replace(/<\/?(body|front|div|head|titlePage)[^>]*>/gu, '');

    // Replace linebreaks
    // eslint-disable-next-line
    contentHtml = contentHtml.replace(/<lb(?:\s[^>]*)?\/>/gu, '<br/>');

    // Extract content between each <pb /> and the next <pb /> or end of string
    const regex = /<pb[^>]*facs="([^"]+)"[^>]*\/>([\s\S]*?)(?=<pb[^>]*\/>|$)/gu;

    const facsHtml = {};
    let match;

   // eslint-disable-next-line
    while ((match = regex.exec(contentHtml)) !== null) {
      // eslint-disable-next-line
      const facsMatch = match[1].trim();
      const facs =  facsMatch.startsWith("#") ? facsMatch.slice(1) : facsMatch;
      // eslint-disable-next-line
      facsHtml[facs] = match[2].trim(); // Everything until next <pb /> or end of string
    }

    const fulltextHtml = facsHtml[this.getFacsMapId()];
    // eslint-disable-next-line
    return { type: 'tei', fulltext: dlfUtils.exists(fulltextHtml) ? fulltextHtml + '<br/>' : '' };
};

/**
 * @returns {string}
 * @private
 */
dlfTeiParser.prototype.getFacsMapId = function() {
  if (!isNaN(this.pageId) && this.pageId !== null && this.pageId !== '') {
    return 'f' + String(this.pageId).padStart(4, '0');
  }
  return this.pageId;
}

/**
 *
 * @param {XMLDocument|string} document
 * @returns {XMLDocument}
 * @private
 */
dlfTeiParser.prototype.parseXML = function(document) {
    if (typeof document === 'string' || document instanceof String) {
        return $.parseXML(document);
    }
    return document;
};
