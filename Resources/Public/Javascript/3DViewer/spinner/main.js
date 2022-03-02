var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var lv = /** @class */ (function () {
    function lv() {
        this.observer = new MutationObserver(this.callback);
    }
    /**
     * iterates through all elements and calls function create on them
     */
    lv.prototype.initLoaderAll = function () {
        var divs = document.getElementsByTagName("DIV");
        for (var i = 0; i < divs.length; i++) {
            if (!divs[i].hasChildNodes()) {
                lv.create(divs[i]);
            }
        }
    };
    /**
     * returns list of non-main classes (every except the one that specifies the element)
     * @param classList
     * @param notIncludingClass
     */
    lv.getModifyingClasses = function (classList, notIncludingClass) {
        var modifyingClasses = [];
        for (var i = 0; i < classList.length; i++) {
            if (classList[i] != notIncludingClass) {
                modifyingClasses.push(classList[i]);
            }
        }
        return modifyingClasses;
    };
    /**
     * decides type of passed element and returns its object
     * @param element - pass existing element or null
     * @param classString - classes separated with one space that specifies type of element, optional, only when passing null instead of element
     */
    lv.create = function (element, classString) {
        if (element === void 0) { element = null; }
        var classes = [];
        if (element != null) {
            var listOfClasses = element.classList;
            for (var i = 0; i < listOfClasses.length; i++) {
                classes.push(listOfClasses[i]);
            }
        }
        else if (classString != null) {
            classes = classString.split(" ");
        }
        for (var i = 0; i < classes.length; i++) {
            switch (classes[i]) {
                case "lv-bars":
                    return new lv.Circle(element, lv.CircleType.Bars, lv.getModifyingClasses(classes, "lv-bars"));
                case "lv-squares":
                    return new lv.Circle(element, lv.CircleType.Squares, lv.getModifyingClasses(classes, "lv-squares"));
                case "lv-circles":
                    return new lv.Circle(element, lv.CircleType.Circles, lv.getModifyingClasses(classes, "lv-circles"));
                case "lv-dots":
                    return new lv.Circle(element, lv.CircleType.Dots, lv.getModifyingClasses(classes, "lv-dots"));
                case "lv-spinner":
                    return new lv.Circle(element, lv.CircleType.Spinner, lv.getModifyingClasses(classes, "lv-spinner"));
                case "lv-dashed":
                    return new lv.Circle(element, lv.CircleType.Dashed, lv.getModifyingClasses(classes, "lv-dashed"));
                case "lv-determinate_circle":
                    return new lv.Circle(element, lv.CircleType.DeterminateCircle, lv.getModifyingClasses(classes, "lv-determinate_circle"));
                case "lv-line":
                    return new lv.Bar(element, lv.BarType.Line, lv.getModifyingClasses(classes, "lv-line"));
                case "lv-bordered_line":
                    return new lv.Bar(element, lv.BarType.BorderedLine, lv.getModifyingClasses(classes, "lv-bordered_line"));
                case "lv-determinate_line":
                    return new lv.Bar(element, lv.BarType.DeterminateLine, lv.getModifyingClasses(classes, "lv-determinate_line"));
                case "lv-determinate_bordered_line":
                    return new lv.Bar(element, lv.BarType.DeterminateBorderedLine, lv.getModifyingClasses(classes, "lv-determinate_bordered_line"));
            }
        }
        return null;
    };
    /**
     * observes for changes in DOM and creates new element's objects
     * @param mutationList
     * @param observer
     */
    lv.prototype.callback = function (mutationList, observer) {
        for (var i = 0; i < mutationList.length; i++) {
            if (mutationList[i].type === "childList") {
                try {
                    if (mutationList[i].addedNodes[0].classList.length > 0) {
                        // filling the node with divs when it is empty
                        lv.create(mutationList[i].addedNodes[0]);
                    }
                }
                catch (error) { }
            }
        }
    };
    ;
    lv.prototype.startObserving = function () {
        this.observer.observe(document.body, { childList: true, subtree: true });
    };
    lv.prototype.stopObserving = function () {
        this.observer.disconnect();
    };
    return lv;
}());
(function (lv) {
    /**
     * specifies functions same for all elements
     */
    var ElementBase = /** @class */ (function () {
        function ElementBase(element) {
            this.element = element === null ? document.createElement('div') : element;
        }
        ElementBase.prototype.show = function () {
            this.element.style.display = null;
        };
        ElementBase.prototype.hide = function () {
            this.element.style.display = "none";
        };
        ElementBase.prototype.remove = function () {
            this.element.parentNode.removeChild(this.element);
        };
        ElementBase.prototype.setLabel = function (labelText) {
            this.element.setAttribute("data-label", labelText);
        };
        ElementBase.prototype.removeLabel = function () {
            this.element.removeAttribute("data-label");
        };
        ElementBase.prototype.showPercentage = function () {
            this.element.setAttribute("data-percentage", "true");
        };
        ElementBase.prototype.hidePercentage = function () {
            this.element.removeAttribute("data-percentage");
        };
        ElementBase.prototype.setId = function (idText) {
            this.element.setAttribute("id", idText);
        };
        ElementBase.prototype.removeId = function () {
            this.element.removeAttribute("id");
        };
        /**
         * adds class or classes to element
         * @param classString - string that contains classes separated with one space
         */
        ElementBase.prototype.addClass = function (classString) {
            var classList = classString.split(" ");
            for (var i = 0; i < classList.length; i++) {
                this.element.classList.add(classList[i]);
            }
        };
        /**
         * if element contains specified class or classes, it/they are removed
         * @param classString - string that contains classes separated with one space
         */
        ElementBase.prototype.removeClass = function (classString) {
            var classList = classString.split(" ");
            for (var i = 0; i < classList.length; i++) {
                if (this.element.classList.contains(classList[i])) {
                    this.element.classList.remove(classList[i]);
                }
            }
        };
        /**
         * returns DOM element - needed for placing or removing the element with jquery
         */
        ElementBase.prototype.getElement = function () {
            return this.element;
        };
        /**
         * resets determinate element to 0
         * @param maxValue
         */
        ElementBase.prototype.reset = function (maxValue) {
            this.update('set', 0, maxValue);
        };
        /**
         * sets determinate element to 100%
         * @param maxValue
         */
        ElementBase.prototype.fill = function (maxValue) {
            this.update('set', maxValue, maxValue);
        };
        /**
         * adds positive or negative value to a determinate element
         * @param addValue
         * @param maxValue
         */
        ElementBase.prototype.add = function (addValue, maxValue) {
            this.update('add', addValue, maxValue);
        };
        /**
         * sets loading bar to passed value
         * @param value
         * @param maxValue
         */
        ElementBase.prototype.set = function (value, maxValue) {
            this.update('set', value, maxValue);
        };
        /**
         * initializes an element
         * @param loaderElement
         * @param description
         */
        ElementBase.prototype.initLoader = function (loaderElement, description) {
            // manual addition on specified object
            if (!loaderElement.hasChildNodes()) {
                this.fillElement(loaderElement, description.className, description.divCount);
            }
        };
        /**
         * fills element with appropriate number of divs
         * @param element
         * @param elementClass
         * @param divNumber
         */
        ElementBase.prototype.fillElement = function (element, elementClass, divNumber) {
            for (var i = 0; i < divNumber; i += 1) {
                element.appendChild(document.createElement("DIV"));
            }
            if (elementClass === "lv-determinate_circle" || elementClass === "lv-determinate_line" || elementClass === "lv-determinate_bordered_line") {
                element.lastElementChild.innerHTML = "0";
            }
            if (!element.classList.contains(elementClass)) {
                element.classList.add(elementClass);
            }
        };
        ;
        return ElementBase;
    }());
    lv.ElementBase = ElementBase;
    /**
     * class for linear elements
     */
    var Bar = /** @class */ (function (_super) {
        __extends(Bar, _super);
        /**
         * creates linear element
         * @param element
         * @param barType
         * @param classes
         */
        function Bar(element, barType, classes) {
            if (classes === void 0) { classes = null; }
            var _this = _super.call(this, element) || this;
            _this.divCount = {};
            _this.divCount[BarType.Line] = { className: "lv-line", divCount: 1 };
            _this.divCount[BarType.BorderedLine] = { className: "lv-bordered_line", divCount: 1 };
            _this.divCount[BarType.DeterminateLine] = { className: "lv-determinate_line", divCount: 2 };
            _this.divCount[BarType.DeterminateBorderedLine] = { className: "lv-determinate_bordered_line", divCount: 2 };
            _this.initLoader(_this.element, _this.divCount[barType]);
            for (var i = 0; i < classes.length; i++) {
                _this.element.classList.add(classes[i]);
            }
            return _this;
        }
        /**
         * type specific update function for linear element
         * @param type
         * @param newValue
         * @param maxValue
         */
        Bar.prototype.update = function (type, newValue, maxValue) {
            // getting current width of line from the page
            var line = this.element.firstElementChild;
            var percentage = this.element.lastElementChild;
            var currentWidth = parseFloat(line.style.width);
            // protective condition for empty line
            if (isNaN(currentWidth)) {
                currentWidth = 0;
            }
            // end point of the animation
            var goalWidth;
            if (type === "add") {
                goalWidth = currentWidth + Math.round((newValue / maxValue) * 1000) / 10;
            }
            else if (type === "set") {
                goalWidth = Math.round((newValue / maxValue) * 1000) / 10;
            }
            // prevent overflow from both sides
            if (goalWidth > 100) {
                goalWidth = 100.0;
            }
            if (goalWidth < 0) {
                goalWidth = 0;
            }
            var animation = setInterval(frame, 5);
            function frame() {
                if (currentWidth > goalWidth) { // shortening the line
                    if (currentWidth < goalWidth + 0.01) {
                        clearInterval(animation);
                    }
                    else {
                        currentWidth -= 0.1;
                    }
                }
                else { // extending the line
                    if (currentWidth > goalWidth - 0.01) {
                        clearInterval(animation);
                    }
                    else {
                        currentWidth += 0.1;
                    }
                }
                line.style.width = currentWidth + "%";
                // updating the percentage
                percentage.innerHTML = currentWidth.toFixed(1);
            }
        };
        return Bar;
    }(ElementBase));
    lv.Bar = Bar;
    /**
     * class for square or circular elements
     */
    var Circle = /** @class */ (function (_super) {
        __extends(Circle, _super);
        /**
         * creates square or circular element
         * @param element
         * @param circleType
         * @param classes
         */
        function Circle(element, circleType, classes) {
            if (classes === void 0) { classes = null; }
            var _this = _super.call(this, element) || this;
            _this.divCount = {};
            _this.divCount[CircleType.Bars] = { className: "lv-bars", divCount: 8 };
            _this.divCount[CircleType.Squares] = { className: "lv-squares", divCount: 4 };
            _this.divCount[CircleType.Circles] = { className: "lv-circles", divCount: 12 };
            _this.divCount[CircleType.Dots] = { className: "lv-dots", divCount: 4 };
            _this.divCount[CircleType.DeterminateCircle] = { className: "lv-determinate_circle", divCount: 4 };
            _this.divCount[CircleType.Spinner] = { className: "lv-spinner", divCount: 1 };
            _this.divCount[CircleType.Dashed] = { className: "lv-dashed", divCount: 1 };
            _this.initLoader(_this.element, _this.divCount[circleType]);
            for (var i = 0; i < classes.length; i++) {
                _this.element.classList.add(classes[i]);
            }
            return _this;
        }
        /**
         * type specific update function for non-linear elements
         * @param type
         * @param newValue
         * @param maxValue
         */
        Circle.prototype.update = function (type, newValue, maxValue) {
            var rotationOffset = -45; // initial rotation of the spinning div in css
            // separating individual parts of the circle
            var background = this.element.children[0];
            var overlay = this.element.children[1];
            var spinner = this.element.children[2];
            var percentage = this.element.children[3];
            // getting the colors defined in css
            var backgroundColor = window.getComputedStyle(background).borderTopColor;
            var spinnerColor = window.getComputedStyle(spinner).borderTopColor;
            // computing current rotation of spinning part of circle using rotation matrix
            var rotationMatrix = window.getComputedStyle(spinner).getPropertyValue("transform").split("(")[1].split(")")[0].split(",");
            var currentAngle = Math.round(Math.atan2(parseFloat(rotationMatrix[1]), parseFloat(rotationMatrix[0])) * (180 / Math.PI)) - rotationOffset;
            // safety conditions for full and empty circle (360 <=> 0 and that caused problems)
            if (percentage.innerHTML === "100") {
                currentAngle = 360;
            }
            if (currentAngle < 0) {
                currentAngle += 360;
            }
            // end point of the animation
            var goalAngle;
            if (type === "add") {
                goalAngle = currentAngle + Math.round((newValue / maxValue) * 360);
            }
            else if (type === "set") {
                goalAngle = Math.round((newValue / maxValue) * 360);
            }
            // prevent overflow to both sides
            if (goalAngle > 360) {
                goalAngle = 360;
            }
            if (goalAngle < 0) {
                goalAngle = 0;
            }
            var id = setInterval(frame, 3);
            function frame() {
                if (currentAngle === goalAngle) { // stopping the animation when end point is reached
                    clearInterval(id);
                }
                else {
                    if (currentAngle < goalAngle) { // "filling" the circle
                        if (currentAngle === 90) {
                            background.style.borderRightColor = spinnerColor;
                            overlay.style.borderTopColor = "transparent";
                        }
                        else if (currentAngle === 180) {
                            background.style.borderBottomColor = spinnerColor;
                        }
                        else if (currentAngle === 270) {
                            background.style.borderLeftColor = spinnerColor;
                        }
                        currentAngle += 1;
                    }
                    else { // "emptying the circle"
                        if (currentAngle === 270) {
                            background.style.borderLeftColor = backgroundColor;
                        }
                        else if (currentAngle === 180) {
                            background.style.borderBottomColor = backgroundColor;
                        }
                        else if (currentAngle === 90) {
                            background.style.borderRightColor = backgroundColor;
                            overlay.style.borderTopColor = backgroundColor;
                        }
                        currentAngle -= 1;
                    }
                    // rotating the circle
                    spinner.style.transform = "rotate(" + (rotationOffset + currentAngle).toString() + "deg)";
                    // updating percentage
                    percentage.innerHTML = (Math.round((currentAngle / 360) * 100)).toString();
                }
            }
        };
        return Circle;
    }(ElementBase));
    lv.Circle = Circle;
    /**
     * list of linear elements
     */
    var BarType;
    (function (BarType) {
        BarType[BarType["Line"] = 0] = "Line";
        BarType[BarType["BorderedLine"] = 1] = "BorderedLine";
        BarType[BarType["DeterminateLine"] = 2] = "DeterminateLine";
        BarType[BarType["DeterminateBorderedLine"] = 3] = "DeterminateBorderedLine";
    })(BarType = lv.BarType || (lv.BarType = {}));
    /**
     * list of non-linear elements
     */
    var CircleType;
    (function (CircleType) {
        CircleType[CircleType["Bars"] = 0] = "Bars";
        CircleType[CircleType["Squares"] = 1] = "Squares";
        CircleType[CircleType["Circles"] = 2] = "Circles";
        CircleType[CircleType["Dots"] = 3] = "Dots";
        CircleType[CircleType["DeterminateCircle"] = 4] = "DeterminateCircle";
        CircleType[CircleType["Spinner"] = 5] = "Spinner";
        CircleType[CircleType["Dashed"] = 6] = "Dashed";
    })(CircleType = lv.CircleType || (lv.CircleType = {}));
})(lv || (lv = {}));

//# sourceMappingURL=main.js.map