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
 * Document class 'tx_dlf_list' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @copyright	Copyright (c) 2011, Sebastian Meyer, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_list implements t3lib_Singleton {

	/**
	 * This holds the documents in sorted order
	 *
	 * @var	array
	 * @access protected
	 */
	protected $elements = array ();

	/**
	 * This holds the number of documents in the list
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $count = 0;

	/**
	 * This holds the list's metadata
	 *
	 * @var	array
	 * @access protected
	 */
	protected $metadata = array ();

	/**
	 * This adds an array of elements at the given position to the list
	 *
	 * @access	public
	 *
	 * @param	array		$elements: Array of elements to add to list
	 * @param	integer		$position: Numeric position for including
	 *
	 * @return	void
	 */
	public function add(array $elements, $position = -1) {

		$position = t3lib_div::intInRange($position, 0, $this->count, $this->count);

		array_splice($this->elements, $position, 0, $elements);

		$this->count = count($this->elements);

	}

	/**
	 * This removes the element at the given position from the list
	 *
	 * @access	public
	 *
	 * @param	integer		$position: Numeric position for removing
	 *
	 * @return	mixed		The removed element
	 */
	public function remove($position) {

		// Save parameter for logging purposes.
		$_position = $position;

		$position = intval($position);

		if ($position < 0 || $position >= $this->count) {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_list->remove('.$_position.')] Invalid position "'.$position.'" for element removing', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

			return;

		}

		$removed = array_splice($this->elements, $position, 1);

		$this->count = count($this->elements);

		return $removed[0];

	}

	/**
	 * This moves the element at the given position up or down
	 *
	 * @access	public
	 *
	 * @param	integer		$position: Numeric position for moving
	 * @param	integer		$steps: Amount of steps to move up or down
	 *
	 * @return	void
	 */
	public function move($position, $steps) {

		// Save parameters for logging purposes.
		$_position = $position;

		$_steps = $steps;

		$position = intval($position);

		// Check if list position is valid.
		if ($position < 0 || $position >= $this->count) {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_list->move('.$_position.', '.$_steps.')] Invalid position "'.$position.'" for element moving', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

			return;

		}

		$steps = intval($steps);

		// Check if moving given amount of steps is possible.
		if (($position + $steps) < 0 || ($position + $steps) >= $this->count) {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_list->move('.$_position.', '.$_steps.')] Invalid steps "'.$steps.'" for moving element at position "'.$position.'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

			return;

		}

		$element = $this->remove($position);

		$this->add(array ($element), $position + $steps);

	}

	/**
	 * This moves the element at the given position up
	 *
	 * @access	public
	 *
	 * @param	integer		$position: Numeric position for moving
	 *
	 * @return	void
	 */
	public function moveUp($position) {

		$this->move($position, -1);

	}

	/**
	 * This moves the element at the given position down
	 *
	 * @access	public
	 *
	 * @param	integer		$position: Numeric position for moving
	 *
	 * @return	void
	 */
	public function moveDown($position) {

		$this->move($position, 1);

	}

	/**
	 * This clears the current list
	 *
	 * @access	public
	 *
	 * @return	void
	 */
	public function reset() {

		$this->elements = array ();

		$this->metadata = array ();

		$this->count = 0;

	}

	/**
	 * This saves the current list
	 *
	 * @access	public
	 *
	 * @param	integer		$pid: PID for saving in database
	 *
	 * @return	void
	 */
	public function save($pid = 0) {

		$pid = max(intval($pid), 0);

		// If no PID is given, save to the user's session instead
		if ($pid > 0) {

			// TODO: Liste in Datenbank speichern (inkl. Sichtbarkeit, Beschreibung, etc.)

		} else {

			tx_dlf_helper::saveToSession(array ($this->elements, $this->metadata), get_class($this));

		}

	}

	/**
	 * This sorts the current list by the given field
	 *
	 * @access	public
	 *
	 * @param	string		$by: Sort the list by this field
	 * @param	boolean		$asc: Sort ascending?
	 *
	 * @return	void
	 */
	public function sort($by, $asc = TRUE) {

		$newOrder = array ();

		$nonSortable = array ();

		foreach ($this->elements as $num => $element) {

			// Is this element sortable?
			if (!empty($element['sorting'][$by])) {

				$newOrder[$element['sorting'][$by].str_pad($num, 6, '0', STR_PAD_LEFT)] = $element;

			} else {

				$nonSortable[] = $element;

			}

		}

		// Reorder elements.
		if ($asc) {

			ksort($newOrder, SORT_LOCALE_STRING);

		} else {

			krsort($newOrder, SORT_LOCALE_STRING);

		}

		// Add non sortable elements to the end of the list.
		$newOrder = array_merge(array_values($newOrder), $nonSortable);

		// Check if something is missing.
		if ($this->count == count($newOrder)) {

			$this->elements = $newOrder;

		} else {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_list->sort('.$by.', ['.($asc ? 'TRUE' : 'FALSE').'])] Sorted list elements do not match unsorted elements', $this->extKey, SYSLOG_SEVERITY_ERROR);

			}

		}

	}

	/**
	 * This returns $this->count via __get()
	 *
	 * @access	protected
	 *
	 * @return	integer		The number of elements in the list
	 */
	protected function _getCount() {

		return $this->count;

	}

	/**
	 * This returns $this->elements via __get()
	 *
	 * @access	protected
	 *
	 * @return	array		The documents in sorted order
	 */
	protected function _getElements() {

		return $this->elements;

	}

	/**
	 * This returns $this->metadata via __get()
	 *
	 * @access	protected
	 *
	 * @return	array		The list's metadata
	 */
	protected function _getMetadata() {

		return $this->metadata;

	}

	/**
	 * This sets $this->metadata via __set()
	 *
	 * @access	protected
	 *
	 * @param	array		$metadata: Array of new metadata
	 *
	 * @return	void
	 */
	protected function _setMetadata(array $metadata = array ()) {

		$this->metadata = $metadata;

	}

	/**
	 * This is the constructor
	 *
	 * @access	public
	 *
	 * @param	array		$elements: Array of documents initially setting up the list
	 * @param	array		$metadata: Array of initial metadata
	 *
	 * @return	void
	 */
	public function __construct(array $elements = array (), array $metadata = array ()) {

		if (empty($elements) && empty($metadata)) {

			// Let's check the user's session.
			$sessionData = tx_dlf_helper::loadFromSession(get_class($this));

			// Restore list from session data.
			if (is_array($sessionData)) {

				if (is_array($sessionData[0])) {

					$this->elements = $sessionData[0];

				}

				if (is_array($sessionData[1])) {

					$this->metadata = $sessionData[1];

				}

			}

		} else {

			// Add metadata to the list.
			$this->metadata = $metadata;

			// Add initial set of elements to the list.
			$this->elements = $elements;

		}

		$this->count = count($this->elements);

	}

	/**
	 * This magic method is invoked each time a clone is called on the object variable
	 * (This method is defined as private/protected because singleton objects should not be cloned)
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function __clone() {}

	/**
	 * This magic method is called each time an invisible property is referenced from the object
	 *
	 * @access	public
	 *
	 * @param	string		$var: Name of variable to get
	 *
	 * @return	mixed		Value of $this->$var
	 */
	public function __get($var) {

		$method = '_get'.ucfirst($var);

		if (!property_exists($this, $var) || !method_exists($this, $method)) {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_list->__get('.$var.')] There is no getter function for property "'.$var.'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

			return;

		} else {

			return $this->$method();

		}

	}

	/**
	 * This magic method is called each time an invisible property is referenced from the object
	 *
	 * @access	public
	 *
	 * @param	string		$var: Name of variable to set
	 * @param	mixed		$value: New value of variable
	 *
	 * @return	void
	 */
	public function __set($var, $value) {

		$method = '_set'.ucfirst($var);

		if (!property_exists($this, $var) || !method_exists($this, $method)) {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_list->__set('.$var.', '.$value.')] There is no setter function for property "'.$var.'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

			}

		} else {

			$this->$method($value);

		}

	}

	/**
	 * This magic method is executed prior to any serialization of the object
	 * @see __wakeup()
	 *
	 * @access	public
	 *
	 * @return	array		Properties to be serialized
	 */
	public function __sleep() {

		return array ('elements', 'metadata');

	}

	/**
	 * This magic method is executed after the object is deserialized
	 * @see __sleep()
	 *
	 * @access	public
	 *
	 * @return	void
	 */
	public function __wakeup() {

		$this->count = count($this->elements);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/common/class.tx_dlf_list.php']);
}

?>