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

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Goobi.Presentation',
	'description' => 'Base plugins, modules, services and API of the Digital Library Framework. It is part of the community-based Goobi Digitization Suite.',
	'category' => 'fe',
	'author' => 'Sebastian Meyer',
	'author_email' => 'sebastian.meyer@slub-dresden.de',
	'author_company' => '<br /><a href="http://www.goobi.org/" target="_blank">Goobi.org</a><br /><a href="https://github.com/goobi" target="_blank">Goobi on Github</a>',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => TRUE,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => FALSE,
	'lockType' => '',
	'version' => '1.3.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-',
			'typo3' => '6.2.0-6.2.99',
			't3jquery' => '2.6.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => '',
);
