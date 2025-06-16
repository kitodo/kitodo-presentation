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
 * @constructor
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
 * @returns {Object}
 */
dlfTeiParser.prototype.parse = function(document) {
    let parsedDoc = this.parseXML(document),
        xml = $(parsedDoc).find('text')[0].innerHTML;

    // Remove tags but keep their content
    xml = xml.replace(/<\/?(body|front|div|head|titlePage)[^>]*>/g, '');

    // Replace linebreaks
    xml = xml.replace(/<lb(?:\s[^>]*)?\/>/g, '<br/>');

    // Extract content between each <pb /> and the next <pb /> or end of string
    const regex = /<pb[^>]*facs="([^"]+)"[^>]*\/>([\s\S]*?)(?=<pb[^>]*\/>|$)/g;

    const facsMap = {};
    let match;

    while ((match = regex.exec(xml)) !== null) {
      const facsMatch = match[1].trim(); // e.g. "#f0002"
      const facs =  facsMatch.startsWith("#") ? facsMatch.slice(1) : facsMatch; // e.g. "f0002"
      const content = match[2].trim(); // everything until next <pb /> or end of string
      facsMap[facs] = content;
    }

    let fulltext = facsMap[this.getFacsMapId()];
    return { type: 'tei', fulltext: dlfUtils.exists(fulltext) ? fulltext + '<br/>' : '' };
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
