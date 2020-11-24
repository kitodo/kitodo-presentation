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
}

/**
 * Method fetches the fulltext data from the server
 */
dlfViewerFullTextDownloadControl.downloadFullTextFile = function() {
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
dlfViewerFullTextDownloadControl.createFullTextFile = function() {
    var fullTextData = dlfUtils.fetchFulltextDataFromServer(this.url, this.image);
    var features = fullTextData.getTextblocks();
    var fileContent = '';

    if (features !== undefined) {
        for (var i = 0; i < features.length; i++) {
            var textLines = features[i].get('textlines');
            for (var j = 0; j < textLines.length; j++) {
                fileContent = fileContent.concat(this.appendTextLine(textLines[j]));
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

    for (var k = 0; k < content.length; k++) {
        var text = content[k].get('fulltext');
        var textLines = text.split(/\n/g);
        for (var l = 0; l < textLines.length; l++) {
            fileContent = fileContent.concat(textLines[l]);
            if (l < textLines.length - 1) {
                fileContent = fileContent.concat('\n');
            }
        }
    }
    return fileContent.concat(fileContent);
};
