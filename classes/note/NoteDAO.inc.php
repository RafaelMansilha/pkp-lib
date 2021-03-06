<?php

/**
 * @file classes/note/NoteDAO.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NoteDAO
 * @ingroup note
 * @see Note
 *
 * @brief Operations for retrieving and modifying Note objects.
 */

import('lib.pkp.classes.note.Note');

class NoteDAO extends DAO {
	/**
	 * Constructor.
	 */
	function NoteDAO() {
		parent::DAO();
	}

	/**
	 * Create a new data object
	 * @return Note
	 */
	function newDataObject() {
		return new Note();
	}

	/**
	 * Retrieve Note by note id
	 * @param $noteId int Note ID
	 * @return Note object
	 */
	function getById($noteId) {
		$result = $this->retrieve(
			'SELECT * FROM notes WHERE note_id = ?', (int) $noteId
		);

		$note = $this->_fromRow($result->GetRowAssoc(false));

		$result->Close();
		return $note;
	}

	/**
	 * Retrieve Notes by user id
	 * @param $userId int User ID
	 * @param $rangeInfo DBResultRange Optional
	 * @return object DAOResultFactory containing matching Note objects
	 */
	function getByUserId($userId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM notes WHERE user_id = ? ORDER BY date_created DESC',
			array((int) $userId),
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve Notes by assoc id/type
	 * @param $assocId int
	 * @param $assocType int
	 * @param $userId int
	 * @return object DAOResultFactory containing matching Note objects
	 */
	function getByAssoc($assocType, $assocId, $userId = null) {
		$params = array((int) $assocId, (int) $assocType);
		if ($userId) $params[] = (int) $userId;

		$result = $this->retrieve(
			'SELECT	*
			FROM	notes
			WHERE	assoc_id = ?
				AND assoc_type = ?
				' . ($userId?' AND user_id = ?':'') . '
			ORDER BY date_created DESC',
			$params
		);
		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve Notes by assoc id/type
	 * @param $assocId int
	 * @param $assocType int
	 * @param $userId int
	 * @return object DAOResultFactory containing matching Note objects
	 */
	function notesExistByAssoc($assocType, $assocId, $userId = null) {
		$params = array((int) $assocId, (int) $assocType);
		if ($userId) $params[] = (int) $userId;

		$result = $this->retrieve(
			'SELECT	COUNT(*)
			FROM	notes
			WHERE	assoc_id = ? AND assoc_type = ?
			' . ($userId?' AND user_id = ?':''),
			$params
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 0 ? false : true;
		$result->Close();

		return $returner;
	}

	/**
	 * Determine whether or not unread notes exist for a given association
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int Foreign key, depending on ASSOC_TYPE
	 * @param $userId int User ID
	 */
	function unreadNotesExistByAssoc($assocType, $assocId, $userId) {
		$params = array((int) $assocId, (int) $assocType, (int) $userId);

		$result = $this->retrieve(
			'SELECT	COUNT(*)
			FROM	notes n
				JOIN item_views v ON (v.assoc_type = ? AND v.assoc_id = CAST(n.note_id AS CHAR) AND v.user_id = ?)
			WHERE	n.assoc_type = ? AND
				n.assoc_id = ? AND
				v.assoc_id IS NULL',
			array(
				(int) ASSOC_TYPE_NOTE,
				(int) $userId,
				(int) $assocType,
				(int) $assocId
			)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 0 ? false : true;
		$result->Close();

		return $returner;
	}

	/**
	 * Creates and returns an note object from a row
	 * @param $row array
	 * @return Note object
	 */
	function _fromRow($row) {
		$note = $this->newDataObject();
		$note->setId($row['note_id']);
		$note->setUserId($row['user_id']);
		$note->setDateCreated($this->datetimeFromDB($row['date_created']));
		$note->setDateModified($this->datetimeFromDB($row['date_modified']));
		$note->setContents($row['contents']);
		$note->setTitle($row['title']);
		$note->setAssocType($row['assoc_type']);
		$note->setAssocId($row['assoc_id']);

		HookRegistry::call('NoteDAO::_fromRow', array(&$note, &$row));

		return $note;
	}

	/**
	 * Inserts a new note into notes table
	 * @param Note object
	 * @return int Note Id
	 */
	function insertObject($note) {
		$this->update(
			sprintf('INSERT INTO notes
				(user_id, date_created, date_modified, title, contents, assoc_type, assoc_id)
				VALUES
				(?, %s, %s, ?, ?, ?, ?)',
				$this->datetimeToDB(Core::getCurrentDate()),
				$this->datetimeToDB(Core::getCurrentDate())
			),
			array(
				(int) $note->getUserId(),
				$note->getTitle(),
				$note->getContents(),
				(int) $note->getAssocType(),
				(int) $note->getAssocId()
			)
		);

		$note->setId($this->getInsertId());
		return $note->getId();
	}

	/**
	 * Update a note in the notes table
	 * @param Note object
	 * @return int Note Id
	 */
	function updateObject($note) {
		return $this->update(
			sprintf('UPDATE	notes SET
					user_id = ?,
					date_created = %s,
					date_modified = %s,
					title = ?,
					contents = ?,
					assoc_type = ?,
					assoc_id = ?
				WHERE	note_id = ?',
				$this->datetimeToDB(Core::getCurrentDate()),
				$this->datetimeToDB(Core::getCurrentDate())
			),
			array(
				(int) $note->getUserId(),
				$note->getTitle(),
				$note->getContents(),
				(int) $note->getAssocType(),
				(int) $note->getAssocId(),
				(int) $note->getId()
			)
		);
	}

	/**
	 * Delete Note by note id
	 * @param $noteId int
	 * @param $userId int optional
	 */
	function deleteById($noteId, $userId = null) {
		$params = array((int) $noteId);
		if ($userId) $params[] = (int) $userId;

		$this->update(
			'DELETE FROM notes WHERE note_id = ?' .
			($userId?' AND user_id = ?':''),
			$params
		);
	}

	/**
	 * Delete Note by association
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int Foreign key, depending on $assocType
	 */
	function deleteByAssoc($assocType, $assocId) {
		$this->update(
			'DELETE FROM notes WHERE assoc_type = ? AND assoc_id = ?',
			array((int) $assocType, (int) $assocId)
		);
	}

	/**
	 * Get the ID of the last inserted note
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('notes', 'note_id');
	}
}

?>
