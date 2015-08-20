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

        if (typeof data == "string") {
            data = OpenLayers.Format.XML.prototype.read.apply(this, [data]);
        }

        // Loop throught the following node types in this order and
        // process the nodes found
        //~ var types = ["TextBlock", "TextLine"];
        var types = ["Page", "PrintSpace", "TextBlock"]; // , "TextLine", "String"];

        for (var i=0, len=types.length; i<len; ++i) {
            var type = types[i];

            var nodes = this.getElementsByTagNameNS(data, this.namespaces.alto, type);

            // skip to next type if no nodes are found
            if(nodes.length == 0) {
                continue;
            }

            switch (type) {

				// Get Page
				case "Page":
					this.parsePrintSpace(nodes);
					break;

				// Get Printspace
                case "PrintSpace":
                   this.parsePrintSpace(nodes);
                   break;

                // Fetch external links
                case "TextBlock":
                   this.parseTextBlock(nodes, options);
                    break;

                // parse style information
                case "TextLine":
                    this.parseTextBlock(nodes, options);
                    break;

                case "String":
                    this.parseTextBlock(nodes, options);
                    break;

            }

        }

        return this.features;
    },

   /**
     * Method: parseTextBlock
     * Finds PrintSpace of Alto files
     *
     * Parameters:
     * nodes   - {Array} of {DOMElement} data to read/parse.
     *
     */
    parsePrintSpace: function(node) {

		var geometry = [];
		var type = node[0].nodeName;

		geometry['width'] = parseInt(node[0].getAttribute("WIDTH"));
		geometry['height'] = parseInt(node[0].getAttribute("HEIGHT"));
		geometry['hpos'] = parseInt(node[0].getAttribute("HPOS"));
		geometry['vpos'] = parseInt(node[0].getAttribute("VPOS"));

 		var feature = {type:type, geometry:geometry};

		this.features = this.features.concat(feature);

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
		var fulltext = '';

		for (var i = 0; i < nodes.length; i++) {

			fulltext = '';

            var lineNodes = this.getElementsByTagNameNS(nodes[i], this.namespaces.alto, "TextLine");

			for (var j = 0; j < lineNodes.length; j++) {

				var wordNodes = this.getElementsByTagNameNS(lineNodes[j], this.namespaces.alto, "*");

				for (var k = 0; k < wordNodes.length; k++) {

					if (wordNodes[k].nodeName == "String") {

						fulltext += wordNodes[k].getAttribute("CONTENT");

					} else if (wordNodes[k].nodeName == "SP") {

						fulltext += ' ';

					} else if (wordNodes[k].nodeName == "HYP") {

						fulltext += '-';

					}

				}

				fulltext += "\n";

			}

			//~ console.log(text);

			features.push(this.parseFeature(nodes[i], fulltext));

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
    parseFeature: function(node, fulltext) {

		var type = node.nodeName;

		// save String attribute if present
		var word = node.getAttribute("CONTENT");

		//extracting the coordinates
		var coords = [];

		coords['x1'] = parseInt(node.getAttribute("HPOS"));
		coords['y1'] = parseInt(node.getAttribute("VPOS"));
		coords['x2'] = parseInt(node.getAttribute("WIDTH")) + coords['x1'];
		coords['y2'] = parseInt(node.getAttribute("HEIGHT")) + coords['y1'];

		var feature = {type:type, coords:coords, word:word, fulltext:fulltext};

        return feature;
    },

    CLASS_NAME: "OpenLayers.Format.ALTO"

});
