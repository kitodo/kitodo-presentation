var dlfViewerFullTextDownloadControl = function(map, image, fullTextUrl) {

    /**
     * @private
     * @type {ol.Map}
     */
    this.map = map;

    /**
     * @type {Object}
     * @private
     */
    this.image = image;

    /**
     * @type {string}
     * @private
     */
    this.url = fullTextUrl;

    // add active / deactive behavior in case of click on control
    var element = $('#tx-dlf-tools-fulltextdownload');
    if (element.length > 0){
        var downloadFullText = $.proxy(function(event) {
            event.preventDefault();

            this.downloadFullTextFile();
        }, this);


        element.on('click', downloadFullText);
        element.on('touchstart', downloadFullText);
    }
};

/**
 * Method fetches the fulltext data from the server
 */
dlfViewerFullTextDownloadControl.prototype.downloadFullTextFile = function() {
    var clickedElement = $('#tx-dlf-tools-fulltextdownload');

    var element = $('<a/>');
    element.attr('id', 'downloadFile');
    element.attr('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(this.createFullTextFile()));
    element.attr('download', 'fulltext.txt');

    element.insertAfter(clickedElement);

    $('#downloadFile').get(0).click();
    $('#downloadFile').remove();
};

/**
 * Activate Fulltext Features
 */
dlfViewerFullTextDownloadControl.prototype.createFullTextFile = function() {
    var fullTextData = dlfFullTextUtils.fetchFullTextDataFromServer(this.url, this.image);
    var features = fullTextData.getTextblocks();
    var fileContent = '';
    if (features !== undefined) {
        for (var feature of features) {
            var textLines = feature.get('textlines');
            for (var textLine of textLines) {
                fileContent = fileContent.concat(this.appendTextLine(textLine));
            }
            fileContent = fileContent.concat('\n\n');
        }
    }

    return fileContent;
};

/**
 * Append text line
 *
 * @param {string} textLine
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
    return fileContent.concat(fileContent);
};
