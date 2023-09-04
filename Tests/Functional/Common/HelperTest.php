<?php

namespace Kitodo\Dlf\Tests\Functional\Common;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use TYPO3\CMS\Core\Localization\LanguageService;

class HelperTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/libraries.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/Common/metadata.xml');
    }

    /**
     * @test
     */
    public function canGetIndexNameFromUid()
    {
        // Repeat to make sure caching isn't broken
        for ($n = 0; $n < 2; $n++) {
            // Good UID, no PID
            $this->assertEquals(
                'default',
                Helper::getIndexNameFromUid(10001, 'tx_dlf_libraries')
            );
            $this->assertEquals(
                'title',
                Helper::getIndexNameFromUid(5001, 'tx_dlf_metadata')
            );
            $this->assertEquals(
                'collection',
                Helper::getIndexNameFromUid(5002, 'tx_dlf_metadata')
            );

            // Good UID, good PID
            $this->assertEquals(
                'default',
                Helper::getIndexNameFromUid(10001, 'tx_dlf_libraries', 20000)
            );
            $this->assertEquals(
                'title',
                Helper::getIndexNameFromUid(5001, 'tx_dlf_metadata', 20000)
            );
            $this->assertEquals(
                'collection',
                Helper::getIndexNameFromUid(5002, 'tx_dlf_metadata', 20000)
            );

            // Good UID, bad PID
            $this->assertEquals(
                '',
                Helper::getIndexNameFromUid(10001, 'tx_dlf_libraries', 123456)
            );

            // Bad UID, no PID
            $this->assertEquals(
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
        $this->assertEquals('German', Helper::getLanguageName('de')); // ISO 639-1
        $this->assertEquals('German', Helper::getLanguageName('ger')); // ISO 639-2
        $this->assertEquals('abcde', Helper::getLanguageName('abcde')); // doesn't match ISO code regex
        $this->assertEquals('abc', Helper::getLanguageName('abc')); // matches ISO code regex, but not an ISO code
    }

    /**
     * @test
     * @group getLanguageName
     */
    public function canTranslateLanguageNameToGerman()
    {
        // NOTE: This only tests in BE mode

        $this->initLanguageService('de');
        $this->assertEquals('Deutsch', Helper::getLanguageName('de')); // ISO 639-1
        $this->assertEquals('Deutsch', Helper::getLanguageName('ger')); // ISO 639-2
        $this->assertEquals('abcde', Helper::getLanguageName('abcde')); // doesn't match ISO code regex
        $this->assertEquals('abc', Helper::getLanguageName('abc')); // matches ISO code regex, but not an ISO code
    }
}
