/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

class RotateControl extends ol.control.Control {
    /**
     * @param {Options=} optOptions Rotate options.
     */
    constructor(optOptions) {
        const options = optOptions ? optOptions : {};

        super({
            element: document.createElement('div'),
            target: options.target,
        });

        const className = options.className !== undefined ? options.className : 'ol-custom-rotate';
        const cssClasses = className + ' ' + ol.css.CLASS_UNSELECTABLE + ' ' + ol.css.CLASS_CONTROL;

        const delta = options.delta !== undefined ? options.delta : 0.5 * Math.PI;

        const rotateLeftClassName = options.rotateLeftClassName !== undefined ? options.rotateLeftClassName : className + '-left';
        const rotateRightClassName = options.rotateRightClassName !== undefined ? options.rotateRightClassName : className + '-right';

        const rotateLeftLabel = options.rotateLeftLabel !== undefined ? options.rotateLeftLabel : '\u21ba';
        const rotateRightLabel = options.rotateRightLabel !== undefined ? options.rotateRightLabel : '\u21bb';

        const rotateLeftTipLabel = options.rotateLeftTipLabel !== undefined ? options.rotateLeftTipLabel : 'Rotate left';
        const rotateRightTipLabel = options.rotateRightTipLabel !== undefined ? options.rotateRightTipLabel : 'Rotate right';

        const leftElement = document.createElement('button');
        leftElement.className = rotateLeftClassName;
        leftElement.setAttribute('type', 'button');
        leftElement.title = rotateLeftTipLabel;
        leftElement.appendChild(typeof rotateLeftLabel === 'string' ? document.createTextNode(rotateLeftLabel) : rotateLeftLabel);
        leftElement.addEventListener(
            'click',
            this.handleClick_.bind(this, -delta),
            false
        );
        const rightElement = document.createElement('button');
        rightElement.className = rotateRightClassName;
        rightElement.setAttribute('type', 'button');
        rightElement.title = rotateRightTipLabel;
        rightElement.appendChild(typeof rotateRightLabel === 'string' ? document.createTextNode(rotateRightLabel) : rotateRightLabel);
        rightElement.addEventListener(
            'click',
            this.handleClick_.bind(this, delta),
            false
        );

        const element = this.element;
        element.className = cssClasses;
        element.appendChild(leftElement);
        element.appendChild(rightElement);

        /**
         * @type {number}
         * @private
         */
        this.duration_ = options.duration !== undefined ? options.duration : 250;
    }

    /**
     * @param {number} delta Rotate delta.
     * @param {MouseEvent} event The event to handle
     * @private
     */
    handleClick_(delta, event) {
        event.preventDefault();
        this.rotateByDelta_(delta);
    }

    /**
     * @param {number} delta Rotate delta.
     * @private
     */
    rotateByDelta_(delta) {
        const view = this.getMap().getView();
        if (!view) {
            // the map does not have a view, so we can't act
            // upon it
            return;
        }
        const currentRotation = view.getRotation();
        if (currentRotation !== undefined) {
            var newRotation = (currentRotation + delta) % (2 * Math.PI);
            if (newRotation < 0) {
                newRotation += 2 * Math.PI;
            }
            if (this.duration_ > 0) {
                if (view.getAnimating()) {
                    view.cancelAnimations();
                }
                view.animate({
                    rotation: newRotation,
                    duration: this.duration_,
                    easing: ol.easing.easeOut,
                });
            } else {
                view.setRotation(newRotation);
            }
        }
    }
}
