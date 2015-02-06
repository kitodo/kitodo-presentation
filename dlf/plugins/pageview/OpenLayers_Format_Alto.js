/**
/**
 * @requires OpenLayers/Format/XML.js
 *
 */
OpenLayers.Format.ALTO = OpenLayers.Class(OpenLayers.Format.XML, {

    /**
     * Property: namespaces
     * {Object} Mapping of namespace aliases to namespace URIs.
     */
    namespaces: {
        alto: "http://www.loc.gov/standards/alto/ns-v2#",
        xsi: "http://www.w3.org/2001/XMLSchema-instance"
    },

    /**
     * Property: features
     * {Array} Array of features
     *
     */
    features: null,

	initialize: function(options) {

        OpenLayers.Format.XML.prototype.initialize.apply(this, [options]);

    },

    /**
     * APIMethod: read
     * Read data from a string, and return a list of features.
     *
     * Parameters:
     * data    - {String} or {DOMElement} data to read/parse.
     *
     * Returns:
     * {Array(<OpenLayers.Feature.Vector>)} List of features.
     */
    read: function(data) {
        this.features = [];

        // Set default options
        var options = {
            depth: 0,
        };

        return this.parseData(data, options);
    },

   /**
     * Method: parseData
     * Read data from a string, and return a list of features.
     *
     * Parameters:
     * data    - {String} or {DOMElement} data to read/parse.
     * options - {Object} Hash of options
     *
     * Returns:
     * {Array(<OpenLayers.Feature.Vector>)} List of features.
     */
    parseData: function(data, options) {

        if(typeof data == "string") {
            data = OpenLayers.Format.XML.prototype.read.apply(this, [data]);
        }

        // Loop throught the following node types in this order and
        // process the nodes found
        //~ var types = ["TextBlock", "TextLine", "String"];
        var types = ["PrintSpace", "TextBlock", "TextLine", "String", "SP", "HYP"];

        for(var i=0, len=types.length; i<len; ++i) {
            var type = types[i];

            var nodes = this.getElementsByTagNameNS(data, this.namespaces.alto, type);

            // skip to next type if no nodes are found
            if(nodes.length == 0) {
                continue;
            }

            switch (type) {

                // Get Printspace
                case "PrintSpace":
                   this.parseTextBlock(nodes, options);
                    //~ var geometry = this.parsePrintSpace(nodes);

					// can't we scale already here?
                    //~ tx_dlf_viewer.setOrigImage(geometry[0], geometry[1]);

                    break;

                // Fetch external links
                case "TextBlock":
                   this.parseTextBlock(nodes, options);
                    break;

                // parse style information
                case "TextLine":
                    this.parseTextBlock(nodes, options);
					//~ this.parseStyles(nodes, options);
                    break;

                case "String":
                    this.parseTextBlock(nodes, options);
                        //~ this.parseStyleMaps(nodes, options);
                    break;

            }

        }
        //~ console.dir(this.features);

        return this.features;
    },

   /**
     * Method: parseTextBlock
     * Finds TextBlocks of Alto files
     *
     * Parameters:
     * nodes   - {Array} of {DOMElement} data to read/parse.
     * options - {Object} Hash of options
     *
     */
    parsePrintSpace: function(nodes) {

		var geometry = [];

		geometry.push(parseInt(nodes[0].getAttribute("WIDTH")));
		geometry.push(parseInt(nodes[0].getAttribute("HEIGHT")));

        return geometry;

    },

   /**
     * Method: parseTextBlock
     * Finds TextBlocks of Alto files
     *
     * Parameters:
     * nodes   - {Array} of {DOMElement} data to read/parse.
     * options - {Object} Hash of options
     *
     */
    parseTextBlock: function(nodes, options) {

		var features = [];

		for (var i = 0; i < nodes.length; i++) {

			features.push(this.parseFeature(nodes[i]));

        }

        // add new features to existing feature list
        this.features = this.features.concat(features);

    },


	/**
	 * Method: parseFeature
	 * This function is the core of the GML parsing code in OpenLayers.
	 *    It creates the geometries that are then attached to the returned
	 *    feature, and calls parseAttributes() to get attribute data out.
	 *
	 * Parameters:
	 * node - {DOMElement} A GML feature node.
	 */
    parseFeature: function(node) {

		var type = node.nodeName;

		// save String attribute if present
		var word = node.getAttribute("CONTENT");

		//extracting the coordinates
		var coords = [];

		coords['x1'] = parseInt(node.getAttribute("HPOS"));
		coords['y1'] = parseInt(node.getAttribute("VPOS"));
		coords['x2'] = parseInt(node.getAttribute("WIDTH")) + coords['x1'];
		coords['y2'] = parseInt(node.getAttribute("HEIGHT")) + coords['y1'];

		var feature = {type:type, coords:coords, word:word};

        return feature;
    },

    CLASS_NAME: "OpenLayers.Format.ALTO"

});
