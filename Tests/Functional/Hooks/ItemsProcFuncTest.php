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

namespace Kitodo\Dlf\Tests\Functional\Hooks;

use Kitodo\Dlf\Domain\Repository\DocumentRepository;
use Kitodo\Dlf\Hooks\ItemsProcFunc;
use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;

class ItemsProcFuncTest extends FunctionalTestCase
{
    /**
     * @var DocumentRepository
     */
    protected DocumentRepository $documentRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Hooks/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Hooks/metadata.csv');
        $this->documentRepository = $this->initializeRepository(DocumentRepository::class, 0);

    }

    /**
     * @test
     */
    public function canToolList()
    {
        $this->initLanguageService('default');
        $itemsProcFunc = new ItemsProcFunc();

        $params = [];
        $itemsProcFunc->toolList($params);
        $expected = [
            'items' => [
                ['Score', 'scoretool'],
                ['Fulltext', 'fulltexttool'],
                ['Add Multiview Source', 'multiviewaddsourcetool'],
                ['IIIF Annotations', 'annotationtool'],
                ['Fulltext Download', 'fulltextdownloadtool'],
                ['Image Download', 'imagedownloadtool'],
                ['Image Manipulation', 'imagemanipulationtool'],
                ['Model Download', 'modeldownloadtool'],
                ['PDF Download', 'pdfdownloadtool'],
                ['Search in Document', 'searchindocumenttool']
            ]
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @test
     */
    public function canExtendedSearchList()
    {
        $itemsProcFunc = new ItemsProcFunc();

        $params = [
            'flexParentDatabaseRow' => [
                'pid' => 19999
            ]
        ];
        $itemsProcFunc->extendedSearchList($params);
        $expected = [
            'items' => [
                ['Sammlungen', 'collection'],
                ['Titel', 'title']
            ],
            'flexParentDatabaseRow' => [
                'pid' => 19999
            ]
        ];
        $this->assertEquals($expected, $params);
    }

    /**
     * @test
     */
    public function canGetFacetList()
    {
        $itemsProcFunc = new ItemsProcFunc();

        $params = [
            'flexParentDatabaseRow' => [
                'pid' => 19999
            ]
        ];
        $itemsProcFunc->getFacetsList($params);
        $expected = [
            'items' => [
                ['Sammlungen', 'collection']
            ],
            'flexParentDatabaseRow' => [
                'pid' => 19999
            ]
        ];
        $this->assertEquals($expected, $params);
    }
}
