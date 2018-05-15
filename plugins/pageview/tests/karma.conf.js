/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

module.exports = function(config) {
    config.set({
        frameworks: ['jasmine'],
        files: [
            // external libraries
            '../node_modules/jquery/dist/jquery.js',
            '../../../Resources/Public/Javascript/OpenLayers/ol3-dlf.js',

            // test specifications
            'spec/*.js',

            // files to test
            '../tx_dlf_altoparser.js',
            '../tx_dlf_ol3_source.js',
            '../tx_dlf_utils.js'
        ],
        browsers: ['PhantomJS']
    });
};
