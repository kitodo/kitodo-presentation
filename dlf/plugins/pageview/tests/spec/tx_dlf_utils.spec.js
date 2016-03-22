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
describe('Test suite for the dlfUtils', function() {

    var fulltexts = ['Yo Big wake up wake up baby', 'Mmm', 'Yo...', 'Yo Big wake yo ass up c mon'];
    var features = (function() {
        var features = [];
        for (var i = 0; i < fulltexts.length; i++) {
            var feature = new ol.Feature();
            feature.set('fulltext', fulltexts[i]);
            features.push(feature);
        }
        return features;
    })();

    describe('Test Function - searchFeatureCollectionForText', function() {

        it('Search for word which exists', function() {
            var response = dlfUtils.searchFeatureCollectionForText(features, 'Mmm');
            expect(response instanceof ol.Feature).toBe(true);
            expect(response.get('fulltext')).toBe('Mmm');
        });

        it('Search for word which exists with lower case support', function() {
            var response = dlfUtils.searchFeatureCollectionForText(features, 'mmm');
            expect(response instanceof ol.Feature).toBe(true);
            expect(response.get('fulltext')).toBe('Mmm');
        });

        it('Search for word does not exists', function() {
            var response = dlfUtils.searchFeatureCollectionForText(features, 'Mmmm');
            expect(response).toBe(undefined);
        });
    });

});
