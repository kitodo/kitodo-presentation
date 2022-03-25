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

return [
    'MODS' => [
        'root' => 'mods',
        'namespace' => 'http://www.loc.gov/mods/v3',
        'class' => Kitodo\Dlf\Format\Mods::class,
    ],
    'TEIHDR' => [
        'root' => 'teiHeader',
        'namespace' => 'http://www.tei-c.org/ns/1.0',
        'class' => Kitodo\Dlf\Format\TeiHeader::class,
    ],
    'ALTO' => [
        'root' => 'alto',
        'namespace' => 'http://www.loc.gov/standards/alto/ns-v2#',
        'class' => Kitodo\Dlf\Format\Alto::class,
    ],
    'IIIF1' => [
        'root' => 'IIIF1',
        'namespace' => 'http://www.shared-canvas.org/ns/context.json',
        'class' => '',
    ],
    'IIIF2' => [
        'root' => 'IIIF2',
        'namespace' => 'http://iiif.io/api/presentation/2/context.json',
        'class' => '',
    ],
    'IIIF3' => [
        'root' => 'IIIF3',
        'namespace' => 'http://iiif.io/api/presentation/3/context.json',
        'class' => '',
    ],
];
