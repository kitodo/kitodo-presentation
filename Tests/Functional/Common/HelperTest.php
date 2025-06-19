<?php

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Tests\Functional\Common;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class HelperTest extends FunctionalTestCase
{
    /**
     * Sets up the test environment by importing necessary CSV datasets.
     *
     * This method is called before each test method to ensure that the
     * required data is available for testing.
     *
     * @access public
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Common/libraries.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Common/metadata.csv');
    }

    /**
     * @test
     */
    public function canGetIndexNameFromUid()
    {
        // Repeat to make sure caching isn't broken
        for ($n = 0; $n < 2; $n++) {
            // Good UID, no PID
            self::assertEquals(
                'default',
                Helper::getIndexNameFromUid(10001, 'tx_dlf_libraries')
            );
            self::assertEquals(
                'title',
                Helper::getIndexNameFromUid(5001, 'tx_dlf_metadata')
            );
            self::assertEquals(
                'collection',
                Helper::getIndexNameFromUid(5002, 'tx_dlf_metadata')
            );

            // Good UID, good PID
            self::assertEquals(
                'default',
                Helper::getIndexNameFromUid(10001, 'tx_dlf_libraries', 20000)
            );
            self::assertEquals(
                'title',
                Helper::getIndexNameFromUid(5001, 'tx_dlf_metadata', 20000)
            );
            self::assertEquals(
                'collection',
                Helper::getIndexNameFromUid(5002, 'tx_dlf_metadata', 20000)
            );

            // Good UID, bad PID
            self::assertEquals(
                '',
                Helper::getIndexNameFromUid(10001, 'tx_dlf_libraries', 123456)
            );

            // Bad UID, no PID
            self::assertEquals(
                '',
                Helper::getIndexNameFromUid(123456, 'tx_dlf_libraries')
            );
        }
    }

    /**
     * @test
     * @group getLanguageName
     */
    public function canTranslateLanguageNameToEnglish()
    {
        // NOTE: This only tests in BE mode

        $this->initLanguageService('default');
        self::assertEquals('German', Helper::getLanguageName('de')); // ISO 639-1
        self::assertEquals('German', Helper::getLanguageName('ger')); // ISO 639-2
        self::assertEquals('abcde', Helper::getLanguageName('abcde')); // doesn't match ISO code regex
        self::assertEquals('abc', Helper::getLanguageName('abc')); // matches ISO code regex, but not an ISO code
    }

    /**
     * @test
     * @group getLanguageName
     */
    public function canTranslateLanguageNameToGerman()
    {
        // NOTE: This only tests in BE mode

        $this->initLanguageService('de');
        self::assertEquals('Deutsch', Helper::getLanguageName('de')); // ISO 639-1
        self::assertEquals('Deutsch', Helper::getLanguageName('ger')); // ISO 639-2
        self::assertEquals('abcde', Helper::getLanguageName('abcde')); // doesn't match ISO code regex
        self::assertEquals('abc', Helper::getLanguageName('abc')); // matches ISO code regex, but not an ISO code
    }
}
