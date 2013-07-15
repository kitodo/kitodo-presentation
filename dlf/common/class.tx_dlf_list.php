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

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Document class 'tx_dlf_list' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_list implements ArrayAccess, Countable, Iterator, t3lib_Singleton {

	/**
	 * This holds the number of documents in the list
	 * @see Countable
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $count = 0;

	/**
	 * This holds the list entries in sorted order
	 * @see ArrayAccess
	 *
	 * @var	array()
	 * @access protected
	 */
	protected $elements = array ();

	/**
	 * This holds the list's metadata
	 *
	 * @var	array
	 * @access protected
	 */
	protected $metadata = array ();

	/**
	 * This holds the current list position
	 * @see Iterator
	 *
	 * @var	integer
	 * @access protected
	 */
	protected $position = 0;

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

		// Save parameters for logging purposes.
		$_position = $position;

		$position = t3lib_div::intInRange($position, 0, $this->count, $this->count);

		if (!empty($elements)) {

			array_splice($this->elements, $position, 0, $elements);

			$this->count = count($this->elements);

		}

	}

	/**
	 * This counts the elements
	 * @see Countable::count()
	 *
	 * @access	public
	 *
	 * @return	integer		The number of elements in the list
	 */
	public function count() {

		return $this->count;

	}

	/**
	 * This returns the current element
	 * @see Iterator::current()
	 *
	 * @access	public
	 *
	 * @return	array		The current element
	 */
	public function current() {

		if ($this->valid()) {

			return $this->getRecord($this->elements[$this->position]);

		} else {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_list->current()] Invalid position "'.$this->position.'" for list element', $this->extKey, SYSLOG_SEVERITY_NOTICE);

			}

			return;

		}

	}

	/**
	 * This returns the full record of any list element
	 *
	 * @access	protected
	 *
	 * @param	array		$element: The list element
	 *
	 * @return	array		The element's full record
	 */
	protected function getRecord(array $element) {

		$record = array ();

		if (!empty($element['uid'])) {



		} else {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_list->getRecord([data])] No UID for list element to fetch full record', $this->extKey, SYSLOG_SEVERITY_WARNING, $element);

			}

			// Return list element unchanged.
			$record = $element;

		}

		return $record;

	}

	/**
	 * This returns the current position
	 * @see Iterator::key()
	 *
	 * @access	public
	 *
	 * @return	integer		The current position
	 */
	public function key() {

		return $this->position;

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
	 * This increments the current list position
	 * @see Iterator::next()
	 *
	 * @access	public
	 *
	 * @return	void
	 */
	public function next() {

		$this->position++;

	}

	/**
	 * This checks if an offset exists
	 * @see ArrayAccess::offsetExists()
	 *
	 * @access	public
	 *
	 * @param	mixed		$offset: The offset to check
	 *
	 * @return	boolean		Does the given offset exist?
	 */
	public function offsetExists($offset) {

		return isset($this->elements[$offset]);

	}

	/**
	 * This returns the element at the given offset
	 * @see ArrayAccess::offsetGet()
	 *
	 * @access	public
	 *
	 * @param	mixed		$offset: The offset to return
	 *
	 * @return	array		The element at the given offset
	 */
	public function offsetGet($offset) {

		if ($this->offsetExists($offset)) {

			return $this->getRecord($this->elements[$offset]);

		} else {

			if (TYPO3_DLOG) {

				t3lib_div::devLog('[tx_dlf_list->offsetGet('.$offset.')] Invalid offset "'.$offset.'" for list element', $this->extKey, SYSLOG_SEVERITY_NOTICE);

			}

			return;

		}

	}

	/**
	 * This sets the element at the given offset
	 * @see ArrayAccess::offsetSet()
	 *
	 * @access	public
	 *
	 * @param	mixed		$offset: The offset to set (non-integer offsets will be appended)
	 * @param	mixed		$value: The value to set
	 *
	 * @return	void
	 */
	public function offsetSet($offset, $value) {

		if (t3lib_div::testInt($offset)) {

			$this->elements[$offset] = $value;

		} else {

			$this->elements[] = $value;

		}

		// Re-number the elements.
		$this->elements = array_values($this->elements);

	}

	/**
	 * This removes the element at the given position from the list
	 *
	 * @access	public
	 *
	 * @param	integer		$position: Numeric position for removing
	 *
	 * @return	array		The removed element
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

		return $this->getRecord($removed[0]);

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

		$this->position = 0;

	}

	/**
	 * This resets the list position
	 * @see Iterator::rewind()
	 *
	 * @access	public
	 *
	 * @return	void
	 */
	public function rewind() {

		$this->position = 0;

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
	 * This unsets the element at the given offset
	 * @see ArrayAccess::offsetUnset()
	 *
	 * @access	public
	 *
	 * @param	mixed		$offset: The offset to unset
	 *
	 * @return	void
	 */
	public function offsetUnset($offset) {

		unset ($this->elements[$offset]);

		// Re-number the elements.
		$this->elements = array_values($this->elements);

		$this->count = count($this->elements);

	}

	/**
	 * This checks if the current list position is valid
	 * @see Iterator::valid()
	 *
	 * @access	public
	 *
	 * @return	boolean		Is the current list position valid?
	 */
	public function valid() {

		return isset($this->elements[$this->position]);

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

				t3lib_div::devLog('[tx_dlf_list->__set('.$var.', [data])] There is no setter function for property "'.$var.'"', $this->extKey, SYSLOG_SEVERITY_WARNING, $value);

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