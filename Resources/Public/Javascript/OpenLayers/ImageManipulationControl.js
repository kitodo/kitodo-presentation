/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

class ImageManipulationControl extends ol.control.Control {
    /**
     * @param {Options=} optOptions Image Manipulation options.
     */
    constructor(optOptions) {
        const options = optOptions ? optOptions : {};

        super({
            element: document.createElement('div'),
            target: options.target,
        });

        const className = options.className !== undefined ? options.className : 'ol-custom-image-manipulation';
        const cssClasses = className + ' ' + ol.css.CLASS_UNSELECTABLE + ' ' + ol.css.CLASS_CONTROL;

        const autoOpen = options.autoOpen !== undefined ? options.autoOpen : false;
        const label = options.label !== undefined ? options.label : '\u25d1';
        const tipLabel = options.tipLabel !== undefined ? options.tipLabel : 'Toggle image manipulation';
        const title = options.title !== undefined ? options.title : 'Image Manipulation';

        const button = document.createElement('button');
        button.title = tipLabel;
        button.setAttribute('type', 'button');
        button.appendChild(typeof label === 'string' ? document.createTextNode(label) : label);
        button.addEventListener('click', this, false);

        const element = this.element;
        element.className = cssClasses;
        element.appendChild(button);

        /**
         * @type {Object}
         * @private
         */
        this.filterDefaults_ = {
            contrast: 1,
            saturate: 1,
            brightness: 1,
            'hue-rotate': '0turn',
            invert: 0
        };

        /**
         * @type {Object}
         * @private
         */
        this.activeFilters_ = $.extend({}, this.filterDefaults_);

        // Create dialog with controls.
        this.dialog_ = document.createElement('div');
        this.dialog_.id = 'ol-custom-image-manipulation-dialog';
        this.dialog_.title = title;
        this.dialog_.appendChild(this.createControl_('contrast', 1, 0, 2));
        this.dialog_.appendChild(this.createControl_('saturate', 1, 0, 2));
        this.dialog_.appendChild(this.createControl_('brightness', 1, 0, 2));
        this.dialog_.appendChild(this.createControl_('hue-rotate', 0, -1, 1));
        this.dialog_.appendChild(this.createControl_('invert', 0));
        this.dialog_.appendChild(this.createResetButton_());
        $(this.dialog_).dialog({
            autoOpen,
            closeText: '',
            height: 'auto',
            position: {
                my: 'right top',
                at: 'right bottom',
                of: this.element
            },
            resizable: false,
            width: 'auto'
        });
        $(this.dialog_).on('dialogclose', this, this.handleEvent);
    }

    /**
     * @private
     */
    applyFilters_() {
        var filters = '';
        var canvas = this.getMap().getTargetElement().querySelector('canvas');
        Object.entries(this.activeFilters_).forEach(([filter, value]) => {
            // Set CSS filter function.
            filters += filter + '(' + value + ') ';
            // Set input control value.
            switch (filter) {
                case 'contrast':
                case 'saturate':
                case 'brightness':
                    this.dialog_.querySelector('input[name="' + filter + '"]').value = value;
                    break;
                case 'hue-rotate':
                    this.dialog_.querySelector('input[name="' + filter + '"]').value = value.replace('turn', '');
                    break;
                case 'invert':
                    this.dialog_.querySelector('input[name="' + filter + '"]').checked = value;
                    break;
            }
        });
        canvas.style.filter = filters;
        canvas.style.webkitFilter = filters; // for backwards compatibility
    }

    /**
     * @param {String} name The name of the control
     * @param {String} value The initial value of the control
     * @param {Number?} min The minimum value of the range
     * @param {Number?} max The maximum value of the range
     * @return {Element}
     * @private
     */
    createControl_(name, value, min, max) {
        var label = document.createElement('label');
        label.className = 'ol-custom-image-manipulation-' + name;
        label.for = 'ol-custom-image-manipulation-' + name;
        switch (name) {
            case 'contrast':
                label.textContent = '\u25d1';
                break;
            case 'saturate':
                label.textContent = '\u25cd';
                break;
            case 'brightness':
                label.textContent = '\u2600';
                break;
            case 'hue-rotate':
                label.textContent = '\u25d5';
                break;
            case 'invert':
                label.textContent = '\u25c9';
                break;
        }
        var input = document.createElement('input');
        input.id = 'ol-custom-image-manipulation-' + name;
        input.name = name;
        input.value = value;
        input.addEventListener('input', this, false);
        if (min !== undefined && max !== undefined) {
            input.setAttribute('type', 'range');
            input.setAttribute('step', '0.1');
            input.setAttribute('min', min);
            input.setAttribute('max', max);
        } else {
            input.setAttribute('type', 'checkbox');
        }
        label.appendChild(input);
        return label;
    }

    /**
     * @return {Element}
     * @private
     */
    createResetButton_() {
        var button = document.createElement('button');
        button.name = 'reset';
        button.textContent = '\u27f2';
        button.setAttribute('type', 'button');
        button.addEventListener('click', this, false);
        return button;
    }

    /**
     * @param {Event} event The event to handle
     */
    handleEvent(event) {
        switch (event.type) {
            case 'click':
                event.preventDefault();
                if (event.target.name === 'reset') {
                    this.reset_();
                } else {
                    this.toggleImageManipulation_();
                }
                break;
            case 'dialogclose':
                event.data.reset_();
                break;
            case 'input':
                this.setFilter_(event.target.name, event.target.value);
                break;
        }
    }

    /**
     * @private
     */
    toggleImageManipulation_() {
        if ($(this.dialog_).dialog('isOpen')) {
            $(this.dialog_).dialog('close');
        } else {
            $(this.dialog_).dialog('option', 'appendTo', this.getMap().getTargetElement());
            $(this.dialog_).dialog('open');
        }
    }

    /**
     * @private
     */
    reset_() {
        this.activeFilters_ = $.extend({}, this.filterDefaults_);
        this.applyFilters_();
    }

    /**
     * @param {String} filter The filter to set
     * @param {String} value The value to set the filter to
     * @private
     */
    setFilter_(filter, value) {
        // Sanitize input.
        switch (filter) {
            case 'contrast':
            case 'saturate':
            case 'brightness':
                // Value has to be between 0 and 2.
                value = Math.min(Math.max(parseFloat(value), 0), 2);
                break;
            case 'hue-rotate':
                // Value has to be between -1 and 1 turn.
                value = Math.min(Math.max(parseFloat(value), -1), 1) + 'turn';
                break;
            case 'invert':
                // Value has to be 0 or 1.
                value = this.activeFilters_[filter] ? 0 : 1;
                break;
            default:
                // Filter not supported.
                return;
        }
        this.activeFilters_[filter] = value;
        this.applyFilters_();
    }
}
