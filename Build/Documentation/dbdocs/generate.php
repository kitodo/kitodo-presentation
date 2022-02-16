#!/usr/bin/env php
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

$classLoader = require_once __DIR__ . '/../../../vendor/autoload.php';

$outputPath = $argv[1] ?? null;
if (empty($outputPath) || !is_writable(($outputPath))) {
    echo 'Error: Output path not specified or not writable' . "\n";
    exit(1);
}

putenv('TYPO3_PATH_ROOT=' . __DIR__ . '/public');
putenv('TYPO3_PATH_APP=' . __DIR__);

// For compatibility with TYPO v9
define('PATH_thisScript', __FILE__);

// For request types other than "FE", the configuration manager would try to access the database.
\TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::run(1, \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::REQUESTTYPE_FE);
\TYPO3\CMS\Core\Core\Bootstrap::init($classLoader, false);

$generator = new \Kitodo\DbDocs\Generator();
$tables = $generator->collectTables();
$page = $generator->generatePage($tables);
file_put_contents($outputPath, $page->render());
