/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Kitodo. Key to digital objects e.V. <contact@kitodo.org>
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
module.exports = function(config) {
    config.set({
        frameworks: ['jasmine'],
        files: [
            // external libraries
            '../node_modules/jquery/dist/jquery.js',
            '../../../lib/OpenLayers/ol3-dlf.js',

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
