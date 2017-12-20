/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

describe('Test suite for the dlfUtils', function() {

    var fulltexts = ['Yo Big wake up wake up baby', 'Mmm', 'Yo...', 'Yo Big wake yo ass up c mon', 'Dresden,'];
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
            var response = dlfUtils.searchFeatureCollectionForText(features, 'Mmm')[0];
            expect(response instanceof ol.Feature).toBe(true);
            expect(response.get('fulltext')).toBe('Mmm');
        });

        it('Search for word which exists with lower case support', function() {
            var response = dlfUtils.searchFeatureCollectionForText(features, 'mmm')[0];
            expect(response instanceof ol.Feature).toBe(true);
            expect(response.get('fulltext')).toBe('Mmm');
        });

        it('Search for word does not exists', function() {
            var response = dlfUtils.searchFeatureCollectionForText(features, 'Mmmm');
            expect(response).toBe(undefined);
        });

        it('Match Dresden in case of "Dresden," given', function() {
            var response = dlfUtils.searchFeatureCollectionForText(features, 'Dresden')[0];
            expect(response instanceof ol.Feature).toBe(true);
            expect(response.get('fulltext')).toBe('Dresden,');
        });
    });

});
