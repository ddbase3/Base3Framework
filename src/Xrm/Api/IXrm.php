<?php declare(strict_types=1);

namespace Base3\Xrm\Api;

/**
 * Interface IXrm
 *
 * Defines core methods for cross-resource management (XRM) including entry handling,
 * tagging, allocation, access control, and relationships.
 */
interface IXrm {

	/**
	 * Returns the name of the current XRM context.
	 *
	 * @return string
	 */
	public function getXrmName();

	/**
	 * Deletes or moves an entry to archive depending on the $moveonly flag.
	 *
	 * @param mixed $id Entry identifier
	 * @param bool $moveonly If true, only move to archive
	 * @return void
	 */
	public function delEntry($id, $moveonly = false);

	/**
	 * Stores or updates an XRM entry.
	 *
	 * @param mixed $entry Entry data (e.g. array or object)
	 * @return void
	 */
	public function setEntry($entry);

	/**
	 * Retrieves a single entry by ID.
	 *
	 * @param mixed $id Entry identifier
	 * @return mixed Entry data
	 */
	public function getEntry($id);

	/**
	 * Retrieves multiple entries by their IDs.
	 *
	 * @param array $ids Array of entry IDs
	 * @return array List of entries
	 */
	public function getEntries($ids);

	/**
	 * Retrieves allocation IDs associated with an entry.
	 *
	 * @param mixed $id Entry identifier
	 * @return array List of related IDs
	 */
	public function getAllocIds($id);

	/**
	 * Retrieves entries matching the given filter.
	 *
	 * @param mixed $filter Filter conditions (e.g. array or string)
	 * @return array Filtered entries
	 */
	public function getFilteredEntries($filter);

	/**
	 * Internal entry query method with optional ID-only mode.
	 *
	 * @param mixed $filter Filter conditions
	 * @param bool $idsonly If true, return only IDs
	 * @return array Entries or IDs
	 */
	public function getEntriesIntern($filter, $idsonly = false);

	/**
	 * Returns all known entry IDs.
	 *
	 * @return array List of all entry IDs
	 */
	public function getAllEntryIds();

	/**
	 * Returns entry IDs linked to a specific XRM name.
	 *
	 * @param string $xrmname Target XRM name
	 * @param bool $invert If true, return entries not linked
	 * @return array List of entry IDs
	 */
	public function getXrmEntryIds($xrmname, $invert = false);

	/**
	 * Marks or unmarks an entry as archived.
	 *
	 * @param mixed $id Entry identifier
	 * @param bool $archive Archive state
	 * @return void
	 */
	public function setArchive($id, $archive);

	/**
	 * Connects two entries bidirectionally.
	 *
	 * @param mixed $id1 First entry ID
	 * @param mixed $id2 Second entry ID
	 * @return void
	 */
	public function connect($id1, $id2);

	/**
	 * Removes the connection between two entries.
	 *
	 * @param mixed $id1 First entry ID
	 * @param mixed $id2 Second entry ID
	 * @return void
	 */
	public function disconnect($id1, $id2);

	/**
	 * Adds a tag to an entry.
	 *
	 * @param mixed $id Entry identifier
	 * @param string $tag Tag to assign
	 * @return void
	 */
	public function addTag($id, $tag);

	/**
	 * Removes a tag from an entry.
	 *
	 * @param mixed $id Entry identifier
	 * @param string $tag Tag to remove
	 * @return void
	 */
	public function removeTag($id, $tag);

	/**
	 * Assigns an app to an entry.
	 *
	 * @param mixed $id Entry identifier
	 * @param string $app App identifier
	 * @return void
	 */
	public function addApp($id, $app);

	/**
	 * Removes an app assignment from an entry.
	 *
	 * @param mixed $id Entry identifier
	 * @param string $app App identifier
	 * @return void
	 */
	public function removeApp($id, $app);

	/**
	 * Adds an allocation (link) from one entry to another.
	 *
	 * @param mixed $id1 Source entry ID
	 * @param mixed $id2 Target entry ID
	 * @return void
	 */
	public function addAlloc($id1, $id2);

	/**
	 * Removes an allocation (link) between two entries.
	 *
	 * @param mixed $id1 Source entry ID
	 * @param mixed $id2 Target entry ID
	 * @return void
	 */
	public function removeAlloc($id1, $id2);

	/**
	 * Returns access permissions for the given entry.
	 *
	 * @param mixed $entry Entry data or ID
	 * @return mixed Access level or permission structure
	 */
	public function getAccess($entry);

	/**
	 * Returns the ID of the entry associated with the current user.
	 *
	 * @return mixed Entry ID
	 */
	public function getUserEntryId();

}

