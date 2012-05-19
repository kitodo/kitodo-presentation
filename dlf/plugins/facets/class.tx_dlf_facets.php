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

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Plugin 'DLF: Facets' for the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @copyright	Copyright (c) 2012, Zeutschel GmbH
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_facets extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/collection/class.tx_dlf_facets.php';

	protected function getLastQuery() {
		// Set last query if applicable.
		$lastQuery = array();

		$_list = t3lib_div::makeInstance('tx_dlf_list');

		if (!empty($_list->metadata['options']['source']) && $_list->metadata['options']['source'] == 'search') {
				
			$lastQuery['query'] = $_list->metadata['options']['select'];
			
			$lastQuery['fq'] = $_list->metadata['options']['filter.query'];
			
		}

		return $lastQuery;
	}
	
	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	string		The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		$this->setCache(FALSE);
		
		// Quit without doing anything if required configuration variables are not set.
		if (empty($this->conf['pages']) || empty($this->conf['facets'])) {

			trigger_error('Incomplete configuration for plugin '.get_class($this), E_USER_NOTICE);

			return $content;

		}
		
		// extract search query
		$lastSearch = $this->getLastQuery();

		if (empty($lastSearch['query'])) {
			
			$lastSearch['query'] = '*';
			
		}

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/collection/template.tmpl'), '###TEMPLATE###');

		}

		if (($solr = tx_dlf_solr::solrConnect($this->conf['solrcore'])) !== NULL) {
			
			// get facets to show from plugin configuration
			$facetsToShow = array();
			
			foreach (explode(',', $this->conf['facets']) as $facet) {
				
				$facetsToShow[$facet.'_faceting'] = $facet;
			
			}	

			// create facet query
			$facetParams = array(
				'facet' => 'true',
				'fq' => $lastSearch['fq'],
				'facet.field' => array()
			);
			
			foreach ($facetsToShow as $facet_field => $original_field) {

				$facetParams['facet.field'][] = $facet_field;

			}
			
			// Perform search.
			$result = $solr->search($lastSearch['query'], 0, $this->conf['limit'], $facetParams);

			// Process results.
			$hasOneValue = false;
			
			$content .= '<div class="facets">';
			
			// render reset link
			// $content .= $this->pi_linkTP_keepPIvars('reset', array('query' => $lastSearch['query'], 'fq' => NULL)).'</br>';
			
			foreach ($result->facet_counts->facet_fields as $key => $facet) {
					
				$content .= '<big>'.tx_dlf_helper::translate($facetsToShow[$key], 'tx_dlf_metadata', $this->conf['pages']).'</big><ul class="unstyled">';
				
				$hasOneValue = false;
				
				foreach ($facet as $value_name => $value_count) {

					if ($value_count > 0) {
						
						$hasOneValue = true;
					
						$content .= $this->render($facetsToShow[$key], $value_name, $value_count, $lastSearch);
							
					}

				}
				
				if (!$hasOneValue) {
					
					$content .= '<li>'.'keine Einträge'.'</li>';

				}

				$content .= '</ul>';

			}
			
			$content .= '</div>';
			
		}
		
		return $this->pi_wrapInBaseClass($content);

	}

	protected function render($index_name, $value, $valueCount, $lastSearch) {

		$renderedValue = $value;

		/*
		 * following value translations are kept from class tx_dlf_metadata.
		 * TODO: discuss central utlility function to render index values
		 */

		// Translate name of holding library.
		if ($index_name == 'owner' && !empty($value)) {

			$renderedValue = htmlspecialchars(tx_dlf_helper::translate($value, 'tx_dlf_libraries', $this->conf['pages']));

		// Translate document type.
		} elseif ($index_name == 'type' && !empty($value)) {

			$renderedValue = $this->pi_getLL($value, tx_dlf_helper::translate($value, 'tx_dlf_structures', $this->conf['pages']), FALSE);

		// Translate ISO 639 language code.
		} elseif ($index_name == 'language' && !empty($value)) {

			$renderedValue = htmlspecialchars(tx_dlf_helper::getLanguageName($value));

		} elseif (!empty($value)) {

			$renderedValue = htmlspecialchars($value);

		}
		
		// shorten rendered value to max length (probably a candidate for configuration)
		if (strlen($renderedValue) > 30) {
			
			$pos = strpos($renderedValue, ' ', 30);
			
			$renderedValue = substr($renderedValue, 0, $pos).'...';
			
		}
		 

		// append value count
		$renderedValue .= '&nbsp;('.$valueCount.')';

		$result = '';

		$selectedIndex = -1;
		
		$i = 0;

		// check if given facet is already selected
	 	foreach ($lastSearch['fq'] as $fqPart) {

			$facetSelection = explode(':', $fqPart);

			if (count($facetSelection) == 2) {

				if ($index_name == $facetSelection[0] && $value == $facetSelection[1]) {

					$selectedIndex = $i;

					break;

				}

			}

			$i++;

		}

		$result = '';
		
		// the given value is selected, prepare deselected filter query
		if ($selectedIndex > -1) {

			$result = '<li class="active">';//.$renderedValue.'</li>';

			if (!is_array($lastSearch['fq'])) {

				$lastSearch['fq'] = NULL;

			} else {
					
				unset($lastSearch['fq'][$selectedIndex]);

				$lastSearch['fq'] = array_values($lastSearch['fq']);
				 
			}

		// the given value is NOT selected, prepare selection filter query
		} else {
				
			$result = '<li>';

			if (empty($lastSearch['fq'])) {

				$lastSearch['fq'] = array();

			} else if (!is_array($lastSearch['fq'])) {

				$lastSearch['fq'] = array( $lastSearch['fq'] );

			}
			
			$lastSearch['fq'][] = ($index_name.':'.$value);
		
		}
		
		$result .= '<a href="'
			.$this->pi_linkTP_keepPIvars_url(array('query' => $lastSearch['query'], 'fq' => $lastSearch['fq'])).'">'
			.$renderedValue
			.'</a></li>';	
	
		return $result;

	}

	public static function printArray($array) {
		$result = "";
		
		if (is_array($array)) {
			if (count($array) == 0) {
				$result .= "array is empty";
			} else {
				$result .= "<div class='values'>";
	
				foreach ($array as $key => $value) {
					$result .= '<span class="key">' . $key . "</span>";
					$result .= '<span class="value">' . self::printArray($value) . "</span>";
				}
	
				$result .= "</div>";	
			}
		// } else if (empty($array)) {
			// $result .= "no value";
		} else {
			$result .= (string) ($array);
		}
		
		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/facets/class.tx_dlf_facets.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/facets/class.tx_dlf_facets.php']);
}

?>