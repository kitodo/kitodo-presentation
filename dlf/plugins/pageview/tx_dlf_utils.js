/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Base namespace for utility functions used by the dlf module.
 *
 * @const
 */
var dlfUtils = dlfUtils || {};

/**
 * Returns true if the specified value is not undefiend
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.exists = function(val) {
    return val !== undefined;
};

/**
 * @param {string} name Name of the cookie
 * @return {string} Value of the cookie
 * @TODO replace unescape function
 */
dlfUtils.getCookie = function(name) {

    var results = document.cookie.match("(^|;) ?"+name+"=([^;]*)(;|$)");

    if (results) {

        return unescape(results[2]);

    } else {

        return null;

    }

};

/**
 * Returns true if the specified value is null.
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.isNull = function(val) {
    return val === null;
};

/**
 * Set a cookie value
 *
 * @param {string} name The key of the value
 * @param {?} value The value to save
 */
dlfUtils.setCookie = function(name, value) {

    document.cookie = name+"="+escape(value)+"; path=/";

};