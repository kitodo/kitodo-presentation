goog.provide('ol.format.ALTO');

goog.require('ol.Feature');
goog.require('ol.format.XMLFeature');
goog.require('ol.geom.Polygon');
goog.require('ol.xml');

/**
 * @classdesc
 * Format for reading ALTO format.
 *
 * @constructor
 * @extends {ol.format.XML}
 * @api
 */
ol.format.ALTO = function() {

  goog.base(this);
};
goog.inherits(ol.format.ALTO, ol.format.XMLFeature);

/**
 * Read all features from a GPX source.
 *
 * @function
 * @param {Document|Node|Object|string} source Source.
 * @param {olx.format.ReadOptions=} opt_options Read options.
 * @return {Array.<ol.Feature>} Features.
 * @api stable
 */
ol.format.ALTO.prototype.readFeatures;


/**
 * @inheritDoc
 */
ol.format.ALTO.prototype.readFeaturesFromNode = function(node, opt_options) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  if (!goog.array.contains(ol.format.ALTO.NAMESPACE_URIS_, node.namespaceURI)) {
    return [];
  }
  if (node.localName == 'alto') {
    var features = ol.xml.pushParseAndPop(
      /** @type {Array.<ol.Feature>} */ ([]), ol.format.ALTO.PARSERS_,
      node, []);
    if (goog.isDef(features)) {
      return features;
    } else {
      return [];
    }
  }
  return [];
};

/**
 * @param {Node} node
 * @param {Array.<*>} objectStack Object stack
 * @private
 * @return {Object|undefined} Feature
 */
ol.format.ALTO.parseFeatureFromPageOrPrintSpace_ = function(node, objectStack) {
  var geometry = ol.format.ALTO.parseGeometry_(node),
    width = parseInt(node.getAttribute("WIDTH")),
    height = parseInt(node.getAttribute("HEIGHT")),
    hpos = parseInt(node.getAttribute("HPOS")),
    vpos = parseInt(node.getAttribute("VPOS")),
    type = node.nodeName,
    feature = new ol.Feature(geometry);
  feature.setProperties({
    'type':type,
    'width': width,
    'height': height,
    'hpos': hpos,
    'vpos': vpos
  });
  return feature;
};

/**
 * Parse a rectangle polygon for every given ALTO geometry
 * @param {Node} node
 * @returns {ol.geom.Polygon|undefined}
 * @private
 */
ol.format.ALTO.parseGeometry_ = function(node){
  var width = parseInt(node.getAttribute("WIDTH")),
    height = parseInt(node.getAttribute("HEIGHT")),
    x1 = parseInt(node.getAttribute("HPOS")),
    y1 = parseInt(node.getAttribute("VPOS")),
    x2 = x1 + width,
    y2 = y1 + height,
    coordinates = [[[x1, y1], [x2, y1], [x2, y2], [x1, y2], [x1, y1]]];

  if (isNaN(width) || isNaN(height))
    return undefined;
  return new ol.geom.Polygon(coordinates);
};

/**
 * @param {Node} node Node.
 * @param {Array.<*>} objectStack Object stack.
 * @private
 * @return {Object|undefined} ComposedBlock object.
 */
ol.format.ALTO.readComposedBlock_ = function(node, objectStack) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  goog.asserts.assert(node.localName == 'ComposedBlock',
    'localName should be ComposedBlock');

  return ol.xml.pushParseAndPop(
    ([]), ol.format.ALTO.COMPOSEDBLOCK_PARSERS_, node, objectStack);
};

/**
 * @param {Node} node Node.
 * @param {Array.<*>} objectStack Object stack.
 * @private
 * @return {Object|undefined} HYP object.
 */
ol.format.ALTO.readHYP_ = function(node, objectStack) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  goog.asserts.assert(node.localName == 'HYP',
    'localName should be HYP');
  var fulltext = objectStack[objectStack.length - 1];
  return fulltext + '-';
};

/**
 * @param {Node} node Node.
 * @param {Array.<*>} objectStack Object stack.
 * @private
 * @return {Object|undefined} Layout object.
 */
ol.format.ALTO.readLayout_ = function(node, objectStack) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  goog.asserts.assert(node.localName == 'Layout',
    'localName should be Layout');
  var featureArr = [],
    value = ol.xml.pushParseAndPop(
      (featureArr), ol.format.ALTO.LAYOUT_PARSERS_, node, objectStack);
  return value;
};

/**
 * @param {Node} node Node.
 * @param {Array.<*>} objectStack Object stack.
 * @private
 * @return {Object|undefined} Page object.
 */
ol.format.ALTO.readPage_ = function(node, objectStack) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  goog.asserts.assert(node.localName == 'Page',
    'localName should be Page');

 /* var featureArr = ol.xml.pushParseAndPop(
      ([]), ol.format.ALTO.PAGE_PARSERS_, node, objectStack),*/
  var feature = ol.format.ALTO.parseFeatureFromPageOrPrintSpace_(node);
  var response = ol.xml.pushParseAndPop(
    (feature), ol.format.ALTO.PAGE_PARSERS_, node, objectStack)

/*  if (goog.isDef(feature)) {
    featureArr.push(feature);
  }*/
  return response;
};

/**
 * @param {Node} node Node.
 * @param {Array.<*>} objectStack Object stack.
 * @private
 * @return {Object|undefined} PrintSpace object.
 */
ol.format.ALTO.readPrintSpace_ = function(node, objectStack) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  goog.asserts.assert(node.localName == 'PrintSpace',
    'localName should be PrintSpace');

  var feature = objectStack[objectStack.length - 1];
  if (!goog.isDef(feature.getGeometry()))
    feature = ol.format.ALTO.parseFeatureFromPageOrPrintSpace_(node);


  var featureArr = ol.xml.pushParseAndPop(
        ([]), ol.format.ALTO.PRINTSPACE_PARSERS_, node, objectStack);
  feature.setProperties({
    'features':featureArr
  });
  return feature;
};

/**
 * @param {Node} node Node.
 * @param {Array.<*>} objectStack Object stack.
 * @private
 * @return {Object|undefined} SP object.
 */
ol.format.ALTO.readSP_ = function(node, objectStack) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  goog.asserts.assert(node.localName == 'SP',
    'localName should be SP');
  var fulltext = objectStack[objectStack.length - 1];
  return fulltext + ' ';
};


/**
 * @param {Node} node Node.
 * @param {Array.<*>} objectStack Object stack.
 * @private
 * @return {Object|undefined} String object.
 */
ol.format.ALTO.readString_ = function(node, objectStack) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  goog.asserts.assert(node.localName == 'String',
    'localName should be String');
  var fulltext = objectStack[objectStack.length - 1];
  return fulltext + node.getAttribute('CONTENT');
};


/**
 * @param {Node} node Node.
 * @param {Array.<*>} objectStack Object stack.
 * @private
 * @return {Object|undefined} TextBlock object.
 */
ol.format.ALTO.readTextBlock_ = function(node, objectStack) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  goog.asserts.assert(node.localName == 'TextBlock',
    'localName should be TextBlock');

  var textlineFeatures =  ol.xml.pushParseAndPop(
      ([]), ol.format.ALTO.TEXTBLOCK_PARSERS_, node, objectStack),
    fulltext = '',
    feature = ol.format.ALTO.parseFeatureFromPageOrPrintSpace_(node);

  // get aggregated fulltexts
  for (var i = 0; i < textlineFeatures.length; i++){
    fulltext += textlineFeatures[i].get('fulltext');
  }

  feature.setProperties({
    'textline': textlineFeatures,
    'fulltext':fulltext
  });

  return [feature];
};

/**
 * @param {Node} node Node.
 * @param {Array.<*>} objectStack Object stack.
 * @private
 * @return {Object|undefined} TextLine object.
 */
ol.format.ALTO.readTextLine_ = function(node, objectStack) {
  goog.asserts.assert(node.nodeType == goog.dom.NodeType.ELEMENT,
    'node.nodeType should be ELEMENT');
  goog.asserts.assert(node.localName == 'TextLine',
    'localName should be TextLine');

  var fulltext = ol.xml.pushParseAndPop(
      (['']), ol.format.ALTO.TEXTLINE_PARSERS_, node, objectStack),
    feature = ol.format.ALTO.parseFeatureFromPageOrPrintSpace_(node);

  if (fulltext !== '') {
    fulltext += '\n';
    feature.setProperties({'fulltext':fulltext});
  };

  return [feature];
};



/**
 * @const
 * @private
 * @type {Array.<string>}
 */
ol.format.ALTO.NAMESPACE_URIS_ = [
  'http://www.loc.gov/standards/alto/ns-v2#'
];

/**
 * @const
 * @type {Object.<string, Object.<string, ol.xml.Parser>>}
 * @private
 */
ol.format.ALTO.PARSERS_ = ol.xml.makeParsersNS(
  ol.format.ALTO.NAMESPACE_URIS_, {
    'Layout': ol.xml.makeReplacer(
      ol.format.ALTO.readLayout_
    )
  });

/**
 * @const
 * @type {Object.<string, Object.<string, ol.xml.Parser>>}
 * @private
 */
ol.format.ALTO.COMPOSEDBLOCK_PARSERS_ = ol.xml.makeParsersNS(
  ol.format.ALTO.NAMESPACE_URIS_, {
    'TextBlock': ol.xml.makeArrayExtender(
      ol.format.ALTO.readTextBlock_
    )
  });

/**
 * @const
 * @type {Object.<string, Object.<string, ol.xml.Parser>>}
 * @private
 */
ol.format.ALTO.LAYOUT_PARSERS_ = ol.xml.makeParsersNS(
  ol.format.ALTO.NAMESPACE_URIS_, {
    'Page': ol.xml.makeReplacer(
      ol.format.ALTO.readPage_
    )
  });

/**
 * @const
 * @type {Object.<string, Object.<string, ol.xml.Parser>>}
 * @private
 */
ol.format.ALTO.PAGE_PARSERS_ = ol.xml.makeParsersNS(
  ol.format.ALTO.NAMESPACE_URIS_, {
    'PrintSpace': ol.xml.makeReplacer(
      ol.format.ALTO.readPrintSpace_
    )
  });

/**
 * @const
 * @type {Object.<string, Object.<string, ol.xml.Parser>>}
 * @private
 */
ol.format.ALTO.PRINTSPACE_PARSERS_ = ol.xml.makeParsersNS(
  ol.format.ALTO.NAMESPACE_URIS_, {
    'TextBlock': ol.xml.makeArrayExtender(
      ol.format.ALTO.readTextBlock_
    ),
    'ComposedBlock': ol.xml.makeArrayExtender(
      ol.format.ALTO.readComposedBlock_
    )
  });

/**
 * @const
 * @type {Object.<string, Object.<string, ol.xml.Parser>>}
 * @private
 */
ol.format.ALTO.TEXTBLOCK_PARSERS_ = ol.xml.makeParsersNS(
  ol.format.ALTO.NAMESPACE_URIS_, {
    'TextLine': ol.xml.makeArrayExtender(
      ol.format.ALTO.readTextLine_
    )
  });

/**
 * @const
 * @type {Object.<string, Object.<string, ol.xml.Parser>>}
 * @private
 */
ol.format.ALTO.TEXTLINE_PARSERS_ = ol.xml.makeParsersNS(
  ol.format.ALTO.NAMESPACE_URIS_, {
    'String': ol.xml.makeReplacer(
      ol.format.ALTO.readString_
    ),
    'SP': ol.xml.makeReplacer(
      ol.format.ALTO.readSP_
    ),
    'HYP': ol.xml.makeReplacer(
      ol.format.ALTO.readHYP_
    )
  });
