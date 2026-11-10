#!/bin/bash

VERSION="10.10.0"

wget -O ol.zip "https://github.com/openlayers/openlayers/releases/download/v$VERSION/v$VERSION-package.zip"
unzip -p ol.zip "dist/ol.js" > openlayers.js
unzip -p ol.zip "ol.css" > openlayers.css
rm ol.zip
