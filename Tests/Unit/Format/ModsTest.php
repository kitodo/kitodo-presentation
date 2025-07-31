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

namespace Kitodo\Dlf\Tests\Unit\Format;

use Kitodo\Dlf\Format\Mods;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ModsTest extends UnitTestCase
{
    protected array $metadata = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->metadata = [
            'title' => [],
            'title_sorting' => [],
            'author' => [],
            'place' => [],
            'place_sorting' => [0 => []],
            'year' => [],
            'prod_id' => [],
            'record_id' => [],
            'opac_id' => [],
            'union_id' => [],
            'urn' => [],
            'purl' => [],
            'type' => [],
            'volume' => [],
            'volume_sorting' => [],
            'license' => [],
            'terms' => [],
            'restrictions' => [],
            'out_of_print' => [],
            'rights_info' => [],
            'collection' => [],
            'owner' => [],
            'mets_label' => [],
            'mets_orderlabel' => [],
            'document_format' => ['METS'],
            'year_sorting' => [0 => []],
        ];

    }

    /**
     * @test
     * @group extractMetadata
     */
    public function extractAuthorsIfNoAutRoleTermAssigned(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/modsAuthorNoAutRoleTerm.xml');
        $mods = new Mods();

        $mods->extractMetadata($xml, $this->metadata, false);

        self::assertEquals(
            [
                "AnonymousGiven1 AnonymousFamily1",
                "AnonymousFamily2, AnonymousGiven2"
            ],
            $this->metadata['author']
        );
    }

    /**
     * @test
     * @group extractMetadata
     */
    public function extractAuthorsWithAutRoleTermAssigned(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/modsAuthorWithAutRoleTerm.xml');
        $mods = new Mods();

        $mods->extractMetadata($xml, $this->metadata, false);
        self::assertEquals(
            [
                'May, Jack, I',
                'John Paul, Pope, 1920-2005',
                'Mattox, Douglas E., 1947-',
                '1882-1941, Woolf, Virginia',
                'Eric Alterman'
            ],
            $this->metadata['author']
        );
    }

    /**
     * @test
     * @group extractMetadata
     */
    public function extractPlaces(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/modsOriginInfo.xml');
        $mods = new Mods();

        $mods->extractMetadata($xml, $this->metadata, false);

        self::assertEquals(
            [
                "Dresden",
                "Hamburg",
                "Berlin",
                "München",
            ],
            $this->metadata['place']
        );

        self::assertEquals(
            [
                "Dresden",
            ],
            $this->metadata['place_sorting']
        );
    }

    /**
     * @test
     * @group extractMetadata
     */
    public function extractYears(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/modsOriginInfo.xml');
        $mods = new Mods();

        $mods->extractMetadata($xml, $this->metadata, false);

        self::assertEquals(
            [
                "2019",
                "2018",
                "2021",
                "2020",
            ],
            $this->metadata['year']
        );

        self::assertEquals(
            [
                "2019",
            ],
            $this->metadata['year_sorting']
        );
    }

    /**
     * @test
     * @group extractMetadata
     */
    public function extractPlacesWithElectronicEdInside(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/modsOriginInfoWithEditionElectronicEd.xml');
        $mods = new Mods();

        $mods->extractMetadata($xml, $this->metadata, false);

        self::assertEquals(
            [
                "Dresden",
                "Hamburg",
                "Berlin",
                "München",
            ],
            $this->metadata['place']
        );

        self::assertEquals(
            [
                "Dresden",
            ],
            $this->metadata['place_sorting']
        );
    }

    /**
     * @test
     * @group extractMetadata
     */
    public function extractYearsWithElectronicEdInside(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/../../Fixtures/Format/modsOriginInfoWithEditionElectronicEd.xml');
        $mods = new Mods();

        $mods->extractMetadata($xml, $this->metadata, false);

        self::assertEquals(
            [
                "2019",
                "2018",
                "2021",
                "2020",
            ],
            $this->metadata['year']
        );

        self::assertEquals(
            [
                "2019",
            ],
            $this->metadata['year_sorting']
        );
    }
}
