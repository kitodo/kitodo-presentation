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

namespace Kitodo\Dlf\Updates;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

/**
 * See: https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/ApiOverview/ContentElements/MigrationListType.html
 */
#[UpgradeWizard('dlfPluginsListTypeToCTypeUpdate')]
final class ListTypeToCTypeUpdate extends AbstractListTypeToCTypeUpdate
{
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'dlf_annotation' => 'dlf_annotation',
            'dlf_basket' => 'dlf_basket',
            'dlf_calendar' => 'dlf_calendar',
            'dlf_collection' => 'dlf_collection',
            'dlf_embedded3dviewer' => 'dlf_embedded3dviewer',
            'dlf_validationform' => 'dlf_validationform',
            'dlf_feeds' => 'dlf_feeds',
            'dlf_listview' => 'dlf_listview',
            'dlf_mediaplayer' => 'dlf_mediaplayer',
            'dlf_metadata' => 'dlf_metadata',
            'dlf_navigation' => 'dlf_navigation',
            'dlf_oaipmh' => 'dlf_oaipmh',
            'dlf_pagegrid' => 'dlf_pagegrid',
            'dlf_pageview' => 'dlf_pageview',
            'dlf_search' => 'dlf_search',
            'dlf_statistics' => 'dlf_statistics',
            'dlf_tableofcontents' => 'dlf_tableofcontents',
            'dlf_toolbox' => 'dlf_toolbox'
        ];
    }

    public function getTitle(): string
    {
        return 'Migrates Kitodo.Presentation ListType to CType';
    }

    public function getDescription(): string
    {
        return 'Migrates Kitodo.Presentation ListType to CType, see ' .
            'https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/ApiOverview/ContentElements/MigrationListType.html#plugins-list-type-migration.';
    }
}
