<?php

/**
 * @file classes/announcement/AnnouncementType.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementType
 * @ingroup announcement
 * @see AnnouncementTypeDAO, AnnouncementTypeForm
 *
 * @brief Basic class describing an announcement type.
 */

class AnnouncementType extends DataObject {
	/**
	 * Constructor
	 */
	function AnnouncementType() {
		parent::DataObject();
	}

	//
	// Get/set methods
	//
	/**
	 * Get assoc ID for this annoucement.
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * Set assoc ID for this annoucement.
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Get assoc type for this annoucement.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set assoc Type for this annoucement.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * Get the type of the announcement type.
	 * @return string
	 */
	function getLocalizedTypeName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the type of the announcement type.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the type of the announcement type.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		$this->setData('name', $name, $locale);
	}
}

?>
