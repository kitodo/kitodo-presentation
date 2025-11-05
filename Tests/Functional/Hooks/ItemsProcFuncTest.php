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
    public function canToollist()
    {
        $this->initLanguageService('default');
        $itemsProcFunc = new ItemsProcFunc();

        $params = [];
        $itemsProcFunc->toolList($params);
        $expected = [
            'items' => [
                ['Score', 'tx_dlf_scoretool'],
                ['Fulltext', 'tx_dlf_fulltexttool'],
                ['Add Multiview Source', 'tx_dlf_multiviewaddsourcetool'],
                ['IIIF Annotations', 'tx_dlf_annotationtool'],
                ['Fulltext Download', 'tx_dlf_fulltextdownloadtool'],
                ['Image Download', 'tx_dlf_imagedownloadtool'],
                ['Image Manipulation', 'tx_dlf_imagemanipulationtool'],
                ['Model Download', 'tx_dlf_modeldownloadtool'],
                ['PDF Download', 'tx_dlf_pdfdownloadtool'],
                ['Search in Document', 'tx_dlf_searchindocumenttool']
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
