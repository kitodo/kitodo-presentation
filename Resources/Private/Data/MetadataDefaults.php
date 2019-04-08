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

// Define metadata elements.
// @see http://dfg-viewer.de/en/profile-of-the-metadata/
$metadataDefaults = [
    'type' => [
        'format' => [
            [
                'encoded' => 5,
                'metadataquery' => '$.metadata.[?(@.label==\'Manifest Type\')].value',
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 0,
        'index_stored' => 1,
        'index_indexed' => 0,
        'index_boost' => 1.00,
        'is_sortable' => 1,
        'is_facet' => 1,
        'is_listed' => 1,
        'index_autocomplete' => 0,
    ],
    'title' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => 'concat(./mods:titleInfo/mods:nonSort," ",./mods:titleInfo/mods:title)',
                'metadataquery_sorting' => './mods:titleInfo/mods:title',
            ],
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:note[@type="caption"]',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 5,
                'metadataquery' => '$[label]',
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => "key.wrap = <dt class=\"tx-dlf-metadata-title\">|</dt>\nvalue.required = 1\nvalue.wrap = <dd class=\"tx-dlf-metadata-title\">|</dd>",
        'index_tokenized' => 1,
        'index_stored' => 1,
        'index_indexed' => 1,
        'index_boost' => 2.00,
        'is_sortable' => 1,
        'is_facet' => 0,
        'is_listed' => 1,
        'index_autocomplete' => 1,
    ],
    'volume' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:part/mods:detail/mods:number',
                'metadataquery_sorting' => './mods:part[@type="host"]/@order',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 0,
        'index_stored' => 1,
        'index_indexed' => 0,
        'index_boost' => 1.00,
        'is_sortable' => 1,
        'is_facet' => 0,
        'is_listed' => 1,
        'index_autocomplete' => 0,
    ],
    'author' => [
        'format' => [
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:name',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 5,
                'metadataquery' => "$.metadata.[?(@.label=='Author')].value",
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 1,
        'index_stored' => 1,
        'index_indexed' => 1,
        'index_boost' => 2.00,
        'is_sortable' => 1,
        'is_facet' => 1,
        'is_listed' => 1,
        'index_autocomplete' => 1,
    ],
    'place' => [
        'format' => [
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:origPlace',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 5,
                'metadataquery' => "$.metadata.[?(@.label=='Place of publication')].value",
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 1,
        'index_stored' => 1,
        'index_indexed' => 1,
        'index_boost' => 1.00,
        'is_sortable' => 1,
        'is_facet' => 1,
        'is_listed' => 1,
        'index_autocomplete' => 0,
    ],
    'year' => [
        'format' => [
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:origDate',
                'metadataquery_sorting' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:origDate/@when',
            ],
            [
                'encoded' => 5,
                'metadataquery' => "$.metadata.[?(@.label=='Date of publication')].value",
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 0,
        'index_stored' => 1,
        'index_indexed' => 1,
        'index_boost' => 1.00,
        'is_sortable' => 1,
        'is_facet' => 1,
        'is_listed' => 1,
        'index_autocomplete' => 0,
    ],
    'language' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:language/mods:languageTerm',
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 0,
        'index_stored' => 0,
        'index_indexed' => 1,
        'index_boost' => 1.00,
        'is_sortable' => 0,
        'is_facet' => 1,
        'is_listed' => 0,
        'index_autocomplete' => 0,
    ],
    'collection' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:classification',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:msIdentifier/teihdr:collection',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 5,
                'metadataquery' => "$.metadata.[?(@.label=='Collection')].value",
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 1,
        'index_stored' => 0,
        'index_indexed' => 1,
        'index_boost' => 1.00,
        'is_sortable' => 0,
        'is_facet' => 1,
        'is_listed' => 0,
        'index_autocomplete' => 0,
    ],
    'owner' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:name[./mods:role/mods:roleTerm="own"]/mods:displayForm',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:publisher',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 5,
                'metadataquery' => "$.metadata.[?(@.label=='Owner')].value",
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 0,
        'index_stored' => 0,
        'index_indexed' => 1,
        'index_boost' => 1.00,
        'is_sortable' => 0,
        'is_facet' => 1,
        'is_listed' => 0,
        'index_autocomplete' => 0,
    ],
    'purl' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:identifier[@type="purl"]',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="purl"]',
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.setContentToCurrent = 1\nvalue.typolink.parameter.current = 1\nvalue.wrap = <dd>|</dd>",
        'index_tokenized' => 0,
        'index_stored' => 0,
        'index_indexed' => 0,
        'index_boost' => 1.00,
        'is_sortable' => 0,
        'is_facet' => 0,
        'is_listed' => 0,
        'index_autocomplete' => 0,
    ],
    'urn' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:identifier[@type="urn"]',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="urn"]',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 5,
                'metadataquery' => "$.metadata.[?(@.label=='URN')].value",
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.setContentToCurrent = 1\nvalue.typolink.parameter.current = 1\nvalue.typolink.parameter.prepend = TEXT\nvalue.typolink.parameter.prepend.value = http://nbn-resolving.de/\nvalue.wrap = <dd>|</dd>",
        'index_tokenized' => 0,
        'index_stored' => 0,
        'index_indexed' => 1,
        'index_boost' => 1.00,
        'is_sortable' => 0,
        'is_facet' => 0,
        'is_listed' => 0,
        'index_autocomplete' => 0,
    ],
    'opac_id' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:identifier[@type="opac"]',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="opac"]',
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 0,
        'index_stored' => 0,
        'index_indexed' => 1,
        'index_boost' => 1.00,
        'is_sortable' => 0,
        'is_facet' => 0,
        'is_listed' => 0,
        'index_autocomplete' => 0,
    ],
    'union_id' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:identifier[@type="ppn"]',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="mmid"]',
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 0,
        'index_stored' => 0,
        'index_indexed' => 1,
        'index_boost' => 1.00,
        'is_sortable' => 0,
        'is_facet' => 0,
        'is_listed' => 0,
        'index_autocomplete' => 0,
    ],
    'record_id' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:recordInfo/mods:recordIdentifier',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="recordIdentifier"]',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 5,
                'metadataquery' => "$['@id']",
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 0,
        'index_stored' => 0,
        'index_indexed' => 1,
        'index_boost' => 1.00,
        'is_sortable' => 0,
        'is_facet' => 0,
        'is_listed' => 0,
        'index_autocomplete' => 0,
    ],
    'prod_id' => [
        'format' => [
            [
                'encoded' => 1,
                'metadataquery' => './mods:identifier[@type="kitodo"]',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 2,
                'metadataquery' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="kitodo"]',
                'metadataquery_sorting' => '',
            ],
            [
                'encoded' => 5,
                'metadataquery' => "$.metadata.[?(@.label=='Kitodo')].value",
                'metadataquery_sorting' => '',
            ],
        ],
        'default_value' => '',
        'wrap' => '',
        'index_tokenized' => 0,
        'index_stored' => 0,
        'index_indexed' => 0,
        'index_boost' => 0.00,
        'is_sortable' => 0,
        'is_facet' => 0,
        'is_listed' => 0,
        'index_autocomplete' => 0,
    ]
];
