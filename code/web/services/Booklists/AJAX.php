<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Booklists_AJAX extends JSON_Action {

	/**
	 * Which of the supplied ChiliPAC bib ids exist in the local catalog.
	 *
	 * A booklist is curated in ChiliPAC and can hold items this library does not
	 * own, so the page has to ask before it offers to link to a record or place a
	 * hold on it.
	 */
	function getCatalogBibs() : array {
		$bibIds = $_REQUEST['bibs'] ?? [];
		if (!is_array($bibIds)) {
			$bibIds = [$bibIds];
		}
		//Booklists are paged, so this only ever sees a page worth of items.
		$bibIds = array_slice(array_values(array_unique(array_map('strval', $bibIds))), 0, 100);
		if (empty($bibIds)) {
			return [
				'success' => true,
				'bibs' => [],
			];
		}

		//Scope to the same indexing profile the booklist page builds its record links
		//and covers from, so a matching identifier from another source cannot pass.
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$recordSource = $indexingProfile->find(true) ? $indexingProfile->name : 'ils';

		global $aspen_db;
		$placeholders = implode(',', array_fill(0, count($bibIds), '?'));
		$statement = $aspen_db->prepare("SELECT DISTINCT identifier FROM grouped_work_primary_identifiers WHERE type = ? AND identifier IN ($placeholders)");
		$statement->execute(array_merge([$recordSource], $bibIds));

		return [
			'success' => true,
			'bibs' => $statement->fetchAll(PDO::FETCH_COLUMN),
		];
	}
}
