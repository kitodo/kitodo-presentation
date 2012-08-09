<?php
/***************************************************************
 *  Copyright notice
*
*  (c) 2011 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
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
	'author_company' => '<br /><a href="http://www.slub-dresden.de/en/" target="_blank">Saxon State and University Library Dresden</a><br /><a href="https://launchpad.net/goobi" target="_blank">The Goobi Community</a>',
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
	'version' => '1.1.99',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-',
			'typo3' => '4.5.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'realurl' => '',
			't3jquery' => '',
		),
	),
	'_md5_values_when_last_written' => '',
);

?>