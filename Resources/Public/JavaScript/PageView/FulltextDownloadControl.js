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

var dlfViewerFullTextDownloadControl = function(map, fulltextData) {

    /**
     * @private
     * @type {ol.Map}
     */
    this.map = map;

    /**
     * @type {FullTextFeature | undefined}
     * @private
     */
    this.fulltextData_ = undefined;

    // add active / deactive behavior in case of click on control
    this.element_ = $('#tx-dlf-tools-fulltextdownload');
    if (this.element_.length > 0){
        this.element_.addClass('disabled');

        var downloadFullText = $.proxy(function(event) {
            event.preventDefault();

            this.downloadFullTextFile();
        }, this);


        this.element_.on('click', downloadFullText);
        this.element_.on('touchstart', downloadFullText);
    }
};

/**
 * Set fulltext data to be used when clicking the button.
 *
 * @param {FullTextFeature} fulltextData
 */
dlfViewerFullTextDownloadControl.prototype.setFulltextData = function (fulltextData) {
    this.fulltextData_ = fulltextData;
    this.element_.removeClass('disabled');
};

/**
 * Method fetches the fulltext data from the server
 */
dlfViewerFullTextDownloadControl.prototype.downloadFullTextFile = function() {
    if (this.fulltextData_) {
        var clickedElement = $('#tx-dlf-tools-fulltextdownload');

        var element = $('<a/>');
        element.attr('id', 'downloadFile');
        element.attr('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(this.createFullTextFile(this.fulltextData_)));
        element.attr('download', 'fulltext.txt');

        element.insertAfter(clickedElement);

        $('#downloadFile').get(0).click();
        $('#downloadFile').remove();
    }
};

/**
 * Activate Fulltext Features
 *
 * @param {FullTextFeature} fulltextData
 */
dlfViewerFullTextDownloadControl.prototype.createFullTextFile = function (fulltextData) {
    let fileContent = '';
    if(dlfUtils.exists(fulltextData.type) && fulltextData.type === 'tei') {
      fileContent = dlfUtils.escapeHtml(fulltextData.fulltext);

      // Use regex to replace any whitespace (spaces or tabs) before '<'
      // eslint-disable-next-line
      fileContent = fileContent.replace(/[ \t]+&lt;/gu, '&lt;');

      // Replace every tag except <p> with an empty string
      // eslint-disable-next-line
      fileContent = fileContent.replace(/&lt;(?!\/?p&gt;)[^&]*?&gt;/gu, '');

      // Remove empty lines
      fileContent = fileContent.split('\n').filter(line => line.trim() !== '').join('\n');

      // Replace <p> with newlines
      fileContent = fileContent.replace(/&lt;\/?p&gt;/gu, '\n');

      // Remove leading empty lines
      return fileContent.replace(/^\s*\n+/gu, '');
    }


    var features = fulltextData.getTextblocks();
    for (var feature of features) {
      var textLines = feature.get('textlines');
      for (var textLine of textLines) {
        fileContent = fileContent.concat(this.appendTextLine(textLine));
      }
      fileContent = fileContent.concat('\n\n');
    }

    return fileContent;
};

/**
 * Append text line
 *
 * @param {ol.Feature} textLine
 */
dlfViewerFullTextDownloadControl.prototype.appendTextLine = function(textLine) {
    var  fileContent = '';
    var content = textLine.get('content');

    for (var item of content) {
        var fullText = item.get('fulltext');
        var fullTextLines = fullText.split(/\n/g);
        for (const [i, fullTextLine] of fullTextLines.entries()) {
            fileContent = fileContent.concat(fullTextLine);
            if (i < fullTextLines.length - 1) {
                fileContent = fileContent.concat('\n');
            }
        }
    }
    return fileContent;
};


