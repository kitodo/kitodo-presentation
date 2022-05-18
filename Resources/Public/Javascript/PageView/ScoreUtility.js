'use strict';

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

 /**
 * Base namespace for utility functions used by the dlf module.
 *
 * @const
 */
let dlfScoreUtils;
dlfScoreUtils = dlfScoreUtils || {};
const verovioSettings = {
	pageWidth: 1500,
	scale: 25,
	adjustPageWidth: true,
	spacingLinear: .15,
	pageHeight: 60000,
	adjustPageHeight: true
};

/**
 * Method fetches the score data from the server
 * @param {string} url
 * @return {svg}
 * @static
 */
dlfScoreUtils.fetchScoreDataFromServer = function(url) {
    const result = new $.Deferred();

	if (url === '') {
		result.reject();
		return result;
	}

    $.ajax({ url }).done(function (data, status, jqXHR) {
        try {
			const tk = new verovio.toolkit();
            const score = tk.renderData(jqXHR.responseText, verovioSettings);

            if (score === undefined) {
                result.reject();
            } else {
                result.resolve(score);
            }
        } catch (e) {
            console.error(e); // eslint-disable-line no-console
            result.reject();
        }
    });

    return result;
};
