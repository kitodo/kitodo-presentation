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

describe('TestCases for the dlfViewerSource module', function() {

    describe('Test dlfViewerSource.IIIF protocol', function() {

        it('Test dlfViewerSource.IIIF.getMetadataURL for URL ending with slash returns valid metadata URL', function() {
            var url = 'http://localhost:8000/',
              response = dlfViewerSource.IIIF.getMetdadataURL(url);

            expect(response === url + 'info.json').toBe(true);
        });

        it('Test dlfViewerSource.IIIF.getMetadataURL for URL ending without slash returns valid metadata URL', function() {
            var url = 'http://localhost:8000',
              response = dlfViewerSource.IIIF.getMetdadataURL(url);

            expect(response === url + '/info.json').toBe(true);
        });

    });

    describe('Test dlfViewerSource.IIP protocol', function() {

        it('Test dlfViewerSource.IIP.getMetadataURL for URL with FIF parameter returns valid metadata URL', function() {
            var url = 'http://localhost:8000/fcgi-bin/iipsrv.fcgi?FIF=hs-2007-16-a-full_tif.tif',
                response = dlfViewerSource.IIP.getMetdadataURL(url);

            expect(response === url + '&obj=IIP,1.0&obj=Max-size&obj=Tile-size&obj=Resolution-number').toBe(true);
        });

        it('Test dlfViewerSource.IIP.getMetadataURL for URL with missing FIF parameter returns valid metadata URL', function() {
            var url = 'http://localhost:8000/fcgi-bin/iipsrv.fcgi/hs-2007-16-a-full_tif.tif',
                response = dlfViewerSource.IIP.getMetdadataURL(url);

            expect(response === url + '?&obj=IIP,1.0&obj=Max-size&obj=Tile-size&obj=Resolution-number').toBe(true);
        });

        it('Test dlfViewerSource.IIP.parseMetadata for correct metadata string returns metadata object', function() {
            var metadataStr = 'IIP:1.0 \nMax-size:29566 14321 \nTile-size:256 256 \nResolution-number:8 \n',
                response = dlfViewerSource.IIP.parseMetadata(metadataStr);

            expect(response.width).toBe(29566);
            expect(response.height).toBe(14321);
            expect(response.tilesize).toEqual([256, 256]);
            expect(response.resolutions).toEqual([1, 2, 4, 8, 16, 32, 64, 128]);
        });

    });

});