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
var dlfTeiParser = function(pageId) {
    /**
     * @type {string|undefined}
     * @private
     */
    this.pageId = dlfUtils.exists(pageId) ? pageId : undefined;
};

/**
 * @param {XMLDocument|string} document
 * @returns {object}
 */
dlfTeiParser.prototype.parse = function(document) {
    const parsedDoc = this.parseXML(document);
    let content = $(parsedDoc).find('text')[0].innerHTML;

    // remove tags but keep their content
    content = content.replace(/<\/?(body|front|div|head|titlePage)[^>]*>/gu, '');

    // replace linebreaks
    content = content.replace(/<lb(?:\s[^>]*)?\/>/gu, '<br/>');

    // Extract content between each <pb /> and the next <pb /> or end of string
    const regex = /<pb[^>]*facs="([^"]+)"[^>]*\/>([\s\S]*?)(?=<pb[^>]*\/>|$)/gu;

    const facsMap = {};
    let match;

    while ((match = regex.exec(content)) !== null) {
      const facsMatch = match[1].trim(); // e.g. "#f0002"
      const facs =  facsMatch.startsWith("#") ? facsMatch.slice(1) : facsMatch; // e.g. "f0002"
      facsMap[facs] = match[2].trim(); // everything until next <pb /> or end of string
    }

    const fulltextHtml = facsMap[this.getFacsMapId()];
    return { type: 'tei', fulltext: dlfUtils.exists(fulltextHtml) ? fulltextHtml + '<br/>' : '' };
};

/**
 * @return {string}
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
