/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

var imageProcessingUtility = {

  'filter': function(canvas, filters) {
    var ctx = canvas.getContext('2d');
    for (var filter in filters) {
      this.applyFilter(canvas, filter, filters[filter]);
    }
  },

  'applyFilter': function(canvas, filter, value) {
    var ctx = canvas.getContext('2d');
    var imgData = ctx.getImageData(0, 0, ctx.canvas.width, ctx.canvas.height);
    switch (filter) {
      case 'contrast':
        imgData = this.contrast(imgData, value);
        ctx.putImageData(imgData, 0, 0);
        break;
      case 'hue':
        imgData = this.hue(imgData, value);
        ctx.putImageData(imgData, 0, 0);
        break;
      case 'saturation':
        imgData = this.saturation(imgData, value);
        ctx.putImageData(imgData, 0, 0);
        break;
      case 'brightness':
        imgData = this.brightness(imgData, value);
        ctx.putImageData(imgData, 0, 0);
        break;
      case 'invert':
        imgData = this.invert(imgData);
        ctx.putImageData(imgData, 0, 0);
        break;

      default:
        break;
    }
  },

  'contrast': function (imgData, value) {
    var data = imgData.data;
    var contrast = (value * 100 - 100) * 2.55;
    var factor = (255 + contrast) / (255.01 - contrast);

    for(var i=0;i<data.length;i+=4)
    {
      data[i] = factor * (data[i] - 128) + 128;     //r value
      data[i+1] = factor * (data[i+1] - 128) + 128; //g value
      data[i+2] = factor * (data[i+2] - 128) + 128; //b value

    }
    return imgData;  //optional (e.g. for filter function chaining)
  },

  'saturation': function (imgData, value) {
    var data = imgData.data;
    var sv = value;
    var luR = 0.3086; // constant to determine luminance of red. Similarly, for green and blue
    var luG = 0.6094;
    var luB = 0.0820;

    var az = (1 - sv)*luR + sv;
    var bz = (1 - sv)*luG;
    var cz = (1 - sv)*luB;
    var dz = (1 - sv)*luR;
    var ez = (1 - sv)*luG + sv;
    var fz = (1 - sv)*luB;
    var gz = (1 - sv)*luR;
    var hz = (1 - sv)*luG;
    var iz = (1 - sv)*luB + sv;

    for(var i = 0; i < data.length; i += 4)
    {
      var red = data[i]; // Extract original red color [0 to 255]. Similarly for green and blue below
      var green = data[i + 1];
      var blue = data[i + 2];

      var saturatedRed = (az*red + bz*green + cz*blue);
      var saturatedGreen = (dz*red + ez*green + fz*blue);
      var saturateddBlue = (gz*red + hz*green + iz*blue);

      data[i] = saturatedRed;
      data[i + 1] = saturatedGreen;
      data[i + 2] = saturateddBlue;
    }

    return imgData;
  },

  'brightness': function (imgData, value) {
    var data = imgData.data;
    var brightnessMul = value; // brightness multiplier

    for(var i = 0; i < data.length; i += 4)
    {
      data[i] = brightnessMul * data[i];
      data[i + 1] = brightnessMul * data[i + 1];
      data[i + 2] = brightnessMul * data[i + 2];
    }

    return imgData;
  },

  'invert': function (imgData) {
    var data = imgData.data;
    for (var i = 0; i < data.length; i += 4) {
      data[i]     = 255 - data[i];     // red
      data[i + 1] = 255 - data[i + 1]; // green
      data[i + 2] = 255 - data[i + 2]; // blue
    }
    return imgData;
  },

  'hue': function(imgData, value) {
    var data = imgData.data;

    var hue = value / 360;

    var rgb = [0, 0, 0];
    var hsv = [0.0, 0.0, 0.0];
    for (var i = 0; i < data.length; i += 4) {
      // retrieve r,g,b (! ignoring alpha !)
      var r = data[i];
      var g = data[i + 1];
      var b = data[i + 2];
      // convert to hsv
      this.rgbToHsv(r, g, b, hsv);
      // change color if hue near enough from tgtHue
      // adjust hue
      // ??? or do not add the delta ???
      hsv[0] = hue  //+ hueDelta;
      // convert back to rgb
      this.hsvToRgb(rgb, hsv);
      // store
      data[i] = rgb[0];
      data[i + 1] = rgb[1];
      data[i + 2] = rgb[2];
    }

    return imgData;
  },

  'rgbToHsv': function (r, g, b, hsv) {
    var K = 0.0,
      swap = 0;
    if (g < b) {
      swap = g;
      g = b;
      b = swap;
      K = -1.0;
    }
    if (r < g) {
      swap = r;
      r = g;
      g = swap;
      K = -2.0 / 6.0 - K;
    }
    var chroma = r - (g < b ? g : b);
    hsv[0] = Math.abs(K + (g - b) / (6.0 * chroma + 1e-20));
    hsv[1] = chroma / (r + 1e-20);
    hsv[2] = r;
  },

  'hsvToRgb': function (rgb, hsv) {
    var h = hsv[0];
    var s = hsv[1];
    var v = hsv[2];

    // The HUE should be at range [0, 1], convert 1.0 to 0.0 if needed.
    if (h >= 1.0) h -= 1.0;

    h *= 6.0;
    var index = Math.floor(h);

    var f = h - index;
    var p = v * (1.0 - s);
    var q = v * (1.0 - s * f);
    var t = v * (1.0 - s * (1.0 - f));

    switch (index) {
      case 0:
        rgb[0] = v;
        rgb[1] = t;
        rgb[2] = p;
        return;
      case 1:
        rgb[0] = q;
        rgb[1] = v;
        rgb[2] = p;
        return;
      case 2:
        rgb[0] = p;
        rgb[1] = v;
        rgb[2] = t;
        return;
      case 3:
        rgb[0] = p;
        rgb[1] = q;
        rgb[2] = v;
        return;
      case 4:
        rgb[0] = t;
        rgb[1] = p;
        rgb[2] = v;
        return;
      case 5:
        rgb[0] = v;
        rgb[1] = p;
        rgb[2] = q;
        return;
    }
  }
}
