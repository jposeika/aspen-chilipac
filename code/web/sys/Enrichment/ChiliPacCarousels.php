<?php

require_once ROOT_DIR . '/sys/Enrichment/ChilipacApi.php';

/**
 * Builds the data for the ChiliPAC home page carousels.
 *
 * Fetches bibs from ChiliPAC (via ChilipacApi), filters out bibs that are not
 * in the local catalog so we never link to invalid records, trims each bib to
 * the fields the carousel template needs, and caches the result per endpoint.
 */
class ChiliPacCarousels {
	const CACHE_TTL = 2 * 60 * 60; // 2 hours

	/**
	 * @return array{
	 *   recordUrlComponent: string,
	 *   recordSource: string,
	 *   recentReviews: array,
	 *   recentRatings: array,
	 *   highestRated: array,
	 *   trending: array
	 * }
	 */
	public function getCarousels(): array {
		$result = [
			'recordUrlComponent' => 'Record',
			'recordSource' => 'ils',
			'recentReviews' => [],
			'recentRatings' => [],
			'highestRated' => [],
			'trending' => [],
		];

		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		if ($indexingProfile->find(true)) {
			$result['recordUrlComponent'] = $indexingProfile->recordUrlComponent;
			$result['recordSource'] = $indexingProfile->name;
		}

		//The recent endpoint returns two lists (reviews and ratings); the others return a single list.
		$recent = $this->loadCached('recent', function (ChilipacApi $api) {
			return $api->getRecentBibs();
		});
		if ($recent !== null) {
			$result['recentReviews'] = $recent['reviews'] ?? [];
			$result['recentRatings'] = $recent['ratings'] ?? [];
		}

		$highestRated = $this->loadCached('highest_rated', function (ChilipacApi $api) {
			$bibs = $api->getHighestRatedBibs();
			return $bibs === null ? null : ['items' => $bibs];
		});
		if ($highestRated !== null) {
			$result['highestRated'] = $highestRated['items'] ?? [];
		}

		$trending = $this->loadCached('trending', function (ChilipacApi $api) {
			$bibs = $api->getTrendingBibs();
			return $bibs === null ? null : ['items' => $bibs];
		});
		if ($trending !== null) {
			$result['trending'] = $trending['items'] ?? [];
		}

		return $result;
	}

	/**
	 * Return the trimmed/filtered bibs for one endpoint, using the cache when available.
	 * $fetcher receives a ChilipacApi and returns a map of section name => raw bib array (or null on failure).
	 *
	 * @return array|null Map of section name => trimmed/filtered bibs, or null if the API is unavailable.
	 */
	private function loadCached(string $cacheSuffix, callable $fetcher): ?array {
		global $library;
		global $memCache;

		$cacheKey = 'chilipac_' . $cacheSuffix . '_bibs_' . $library->chiliFreshSettingId;
		$data = $memCache->get($cacheKey);
		if ($data === false) {
			$chiliPacApi = ChilipacApi::forLibrary();
			if ($chiliPacApi === null) {
				return null;
			}
			$sections = $fetcher($chiliPacApi);
			if ($sections === null) {
				return null;
			}
			$data = [];
			$allBibIds = [];
			foreach ($sections as $section => $bibs) {
				$data[$section] = $this->trimBibs($bibs);
				$allBibIds = array_merge($allBibIds, array_column($data[$section], 'bib_id'));
			}
			$knownBibIds = $this->getKnownBibIds($allBibIds);
			foreach ($data as $section => $bibs) {
				$data[$section] = array_values(array_filter($bibs, function ($bib) use ($knownBibIds) {
					return isset($knownBibIds[$bib['bib_id']]);
				}));
			}
			$memCache->set($cacheKey, $data, self::CACHE_TTL);
		}
		return $data;
	}

	/**
	 * Reduce raw ChiliPAC bibs to just the fields the carousel template needs, keeping the cached value small.
	 */
	private function trimBibs(array $bibs): array {
		$trimmed = [];
		foreach ($bibs as $bib) {
			if (empty($bib['bib_id'])) {
				continue;
			}
			$trimmed[] = [
				'bib_id' => $bib['bib_id'],
				'title' => $bib['title'] ?? '',
				'author' => $bib['author'] ?? '',
				'isbn' => $bib['isbn'] ?? '',
			];
		}
		return $trimmed;
	}

	/**
	 * Given a list of ChiliPAC bib ids, return the set (as a lookup map) of those present in the local catalog.
	 */
	private function getKnownBibIds(array $bibIds): array {
		global $aspen_db;
		$bibIds = array_values(array_unique($bibIds));
		if (empty($bibIds) || !isset($aspen_db)) {
			return [];
		}
		$placeholders = implode(',', array_fill(0, count($bibIds), '?'));
		$stmt = $aspen_db->prepare("SELECT DISTINCT identifier FROM grouped_work_primary_identifiers WHERE identifier IN ($placeholders)");
		$stmt->execute(array_map('strval', $bibIds));
		return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
	}
}
