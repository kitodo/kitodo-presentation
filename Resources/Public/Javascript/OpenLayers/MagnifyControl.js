/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

class MagnifyControl extends ol.control.Control {
    /**
     * @param {Options=} optOptions Image Manipulation options.
     */
    constructor(optOptions) {
        const options = optOptions ? optOptions : {};

        super({
            element: document.createElement('div'),
            target: options.target,
        });

        const className = options.className !== undefined ? options.className : 'ol-custom-magnify';
        const cssClasses = className + ' ' + ol.css.CLASS_UNSELECTABLE + ' ' + ol.css.CLASS_CONTROL;

        const label = options.label !== undefined ? options.label : '\u2a00';
        const tipLabel = options.tipLabel !== undefined ? options.tipLabel : 'Toggle magnifier';

        const button = document.createElement('button');
        button.title = tipLabel;
        button.setAttribute('type', 'button');
        button.appendChild(typeof label === 'string' ? document.createTextNode(label) : label);
        button.addEventListener('click', this, false);

        const element = this.element;
        element.className = cssClasses;
        element.appendChild(button);

        /**
         * @type {boolean}
         * @private
         */
        this.active_ = false;

        /**
         * @type {Array.<ol.events.EventKey>}
         * @private
         */
        this.events_ = [];

        /**
         * @type {ol.pixel.Pixel}
         * @private
         */
        this.mousePosition = null;
    }

    /**
     * @param {Event} event The event to handle
     */
    handleEvent(event) {
        switch (event.type) {
            case 'click':
                event.preventDefault();
                this.toggleMagnifier_();
                break;
            case 'mousemove':
                this.moveMagnifier_(event);
                break;
            case 'mouseout':
                this.pauseMagnifier_();
                break;
        }
    }

    /**
     * @param {Event} event The event to handle
     * @private
     */
    moveMagnifier_(event) {
        this.mousePosition = this.getMap().getEventPixel(event);
        this.getMap().render();
    }

    /**
     * @private
     */
    pauseMagnifier_() {
        this.mousePosition = null;
        this.getMap().render();
    }

    /**
     * @param {Event} event The postrender event
     */
    postrender(event) {
        if (this.active_ && this.mousePosition) {
            var radius = 75;
            var pixel = ol.render.getRenderPixel(event, this.mousePosition);
            var offset = ol.render.getRenderPixel(
                event,
                [
                    this.mousePosition[0] + radius,
                    this.mousePosition[1]
                ]
            );
            var half = Math.sqrt(
                Math.pow(offset[0] - pixel[0], 2) + Math.pow(offset[1] - pixel[1], 2)
            );
            var context = event.context;
            var centerX = pixel[0];
            var centerY = pixel[1];
            var originX = centerX - half;
            var originY = centerY - half;
            var size = Math.round(2 * half + 1);
            var sourceData = context.getImageData(originX, originY, size, size).data;
            var dest = context.createImageData(size, size);
            var destData = dest.data;
            for (var j = 0; j < size; ++j) {
                for (var i = 0; i < size; ++i) {
                    var dI = i - half;
                    var dJ = j - half;
                    var dist = Math.sqrt(dI * dI + dJ * dJ);
                    var sourceI = i;
                    var sourceJ = j;
                    if (dist < half) {
                        sourceI = Math.round(half + dI / 2);
                        sourceJ = Math.round(half + dJ / 2);
                    }
                    var destOffset = (j * size + i) * 4;
                    var sourceOffset = (sourceJ * size + sourceI) * 4;
                    destData[destOffset] = sourceData[sourceOffset];
                    destData[destOffset + 1] = sourceData[sourceOffset + 1];
                    destData[destOffset + 2] = sourceData[sourceOffset + 2];
                    destData[destOffset + 3] = sourceData[sourceOffset + 3];
                }
            }
            context.beginPath();
            context.arc(centerX, centerY, half, 0, 2 * Math.PI);
            context.lineWidth = (3 * half) / radius;
            context.strokeStyle = 'rgba(0,60,136,.3)';
            context.putImageData(dest, originX, originY);
            context.stroke();
            context.restore();
        }
    }

    /**
     * @private
     */
    toggleMagnifier_() {
        var canvas = this.getMap().getTargetElement();
        if (this.active_) {
            canvas.removeEventListener('mousemove', this, false);
            canvas.removeEventListener('mouseout', this, false);
            while (this.events_.length > 0) {
                ol.events.unlistenByKey(this.events_.pop());
            }
            this.active_ = false;
        } else {
            canvas.addEventListener('mousemove', this, false);
            canvas.addEventListener('mouseout', this, false);
            this.getMap().getLayers().forEach((layer) => {
                this.events_.push(
                    layer.on('postrender', this.postrender.bind(this))
                );
            });
            this.active_ = true;
        }
        this.getMap().render();
    }
}
