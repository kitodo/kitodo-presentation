This is a custom build of OpenLayers 3. For rebuilding it use the OpenLayers 3 build system together with the file "ol3-dlf.json".

From the ol3 root folder the command for building the library looks like this:

```
node tasks/build.js ~/dlf/Resources/Public/Javascript/OpenLayers/ol3-dlf.json ~/dlf/Resources/Public/Javascript/OpenLayers/ol3-dlf.js
```

Run `npm install` before building an ol3 custom build. Right now the build is based on the [OL3 SLUB fork](https://github.com/slub/ol3). This is due to
the fact that it uses small custom modifications of the ol.source.Zoomify.
