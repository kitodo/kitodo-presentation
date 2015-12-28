<?php
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

// Define metadata elements.
// @see http://dfg-viewer.de/en/profile-of-the-metadata/
$metadata = array (
	'type' => array (
		'format' => array (),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 0,
		'stored' => 1,
		'indexed' => 0,
		'boost' => 1.00,
		'is_sortable' => 1,
		'is_facet' => 1,
		'is_listed' => 1,
		'autocomplete' => 0,
	),
	'title' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => 'concat(./mods:titleInfo/mods:nonSort," ",./mods:titleInfo/mods:title)',
				'xpath_sorting' => './mods:titleInfo/mods:title',
			),
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:note[@type="caption"]',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => "key.wrap = <dt class=\"tx-dlf-metadata-title\">|</dt>\nvalue.required = 1\nvalue.wrap = <dd class=\"tx-dlf-metadata-title\">|</dd>",
		'tokenized' => 1,
		'stored' => 1,
		'indexed' => 1,
		'boost' => 2.00,
		'is_sortable' => 1,
		'is_facet' => 0,
		'is_listed' => 1,
		'autocomplete' => 1,
	),
	'volume' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:part/mods:detail/mods:number',
				'xpath_sorting' => './mods:part[@type="host"]/@order',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 0,
		'stored' => 1,
		'indexed' => 0,
		'boost' => 1.00,
		'is_sortable' => 1,
		'is_facet' => 0,
		'is_listed' => 1,
		'autocomplete' => 0,
	),
	'author' => array (
		'format' => array (
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:name',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 1,
		'stored' => 1,
		'indexed' => 1,
		'boost' => 2.00,
		'is_sortable' => 1,
		'is_facet' => 1,
		'is_listed' => 1,
		'autocomplete' => 1,
	),
	'place' => array (
		'format' => array (
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:origPlace',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 1,
		'stored' => 1,
		'indexed' => 1,
		'boost' => 1.00,
		'is_sortable' => 1,
		'is_facet' => 1,
		'is_listed' => 1,
		'autocomplete' => 0,
	),
	'year' => array (
		'format' => array (
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:origDate',
				'xpath_sorting' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:head/teihdr:origDate/@when',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 0,
		'stored' => 1,
		'indexed' => 1,
		'boost' => 1.00,
		'is_sortable' => 1,
		'is_facet' => 1,
		'is_listed' => 1,
		'autocomplete' => 0,
	),
	'language' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:language/mods:languageTerm',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 0,
		'stored' => 0,
		'indexed' => 1,
		'boost' => 1.00,
		'is_sortable' => 0,
		'is_facet' => 1,
		'is_listed' => 0,
		'autocomplete' => 0,
	),
	'collection' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:classification',
				'xpath_sorting' => '',
			),
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:sourceDesc/teihdr:msDesc/teihdr:msIdentifier/teihdr:collection',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 1,
		'stored' => 0,
		'indexed' => 1,
		'boost' => 1.00,
		'is_sortable' => 0,
		'is_facet' => 1,
		'is_listed' => 0,
		'autocomplete' => 0,
	),
	'owner' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:name[./mods:role/mods:roleTerm="own"]/mods:displayForm',
				'xpath_sorting' => '',
			),
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:publisher',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 0,
		'stored' => 0,
		'indexed' => 1,
		'boost' => 1.00,
		'is_sortable' => 0,
		'is_facet' => 1,
		'is_listed' => 0,
		'autocomplete' => 0,
	),
	'purl' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:identifier[@type="purl"]',
				'xpath_sorting' => '',
			),
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="purl"]',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.setContentToCurrent = 1\nvalue.typolink.parameter.current = 1\nvalue.wrap = <dd>|</dd>",
		'tokenized' => 0,
		'stored' => 0,
		'indexed' => 0,
		'boost' => 1.00,
		'is_sortable' => 0,
		'is_facet' => 0,
		'is_listed' => 0,
		'autocomplete' => 0,
	),
	'urn' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:identifier[@type="urn"]',
				'xpath_sorting' => '',
			),
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="urn"]',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.setContentToCurrent = 1\nvalue.typolink.parameter.current = 1\nvalue.typolink.parameter.prepend = TEXT\nvalue.typolink.parameter.prepend.value = http://nbn-resolving.de/\nvalue.wrap = <dd>|</dd>",
		'tokenized' => 0,
		'stored' => 0,
		'indexed' => 1,
		'boost' => 1.00,
		'is_sortable' => 0,
		'is_facet' => 0,
		'is_listed' => 0,
		'autocomplete' => 0,
	),
	'opac_id' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:identifier[@type="opac"]',
				'xpath_sorting' => '',
			),
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="opac"]',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 0,
		'stored' => 0,
		'indexed' => 1,
		'boost' => 1.00,
		'is_sortable' => 0,
		'is_facet' => 0,
		'is_listed' => 0,
		'autocomplete' => 0,
	),
	'union_id' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:identifier[@type="ppn"]',
				'xpath_sorting' => '',
			),
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="mmid"]',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 0,
		'stored' => 0,
		'indexed' => 1,
		'boost' => 1.00,
		'is_sortable' => 0,
		'is_facet' => 0,
		'is_listed' => 0,
		'autocomplete' => 0,
	),
	'record_id' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:recordInfo/mods:recordIdentifier',
				'xpath_sorting' => '',
			),
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="recordIdentifier"]',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 0,
		'stored' => 0,
		'indexed' => 1,
		'boost' => 1.00,
		'is_sortable' => 0,
		'is_facet' => 0,
		'is_listed' => 0,
		'autocomplete' => 0,
	),
	'prod_id' => array (
		'format' => array (
			array (
				'encoded' => 1,
				'xpath' => './mods:identifier[@type="goobi"]',
				'xpath_sorting' => '',
			),
			array (
				'encoded' => 2,
				'xpath' => './teihdr:fileDesc/teihdr:publicationStmt/teihdr:idno[@type="goobi"]',
				'xpath_sorting' => '',
			),
		),
		'default_value' => '',
		'wrap' => '',
		'tokenized' => 0,
		'stored' => 0,
		'indexed' => 0,
		'boost' => 0.00,
		'is_sortable' => 0,
		'is_facet' => 0,
		'is_listed' => 0,
		'autocomplete' => 0,
	)
);
