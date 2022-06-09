<?php

namespace Kitodo\Dlf\Tests\Functional\Common;

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

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
}
