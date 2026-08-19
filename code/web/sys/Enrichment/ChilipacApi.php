<?php

require_once ROOT_DIR . '/sys/CurlWrapper.php';
require_once ROOT_DIR . '/sys/SystemLogging/ExternalRequestLogEntry.php';

class ChilipacApi {

	private string $baseUrl;
	private string $apiKey;
	private CurlWrapper $client;

	public function __construct(string $baseUrl, string $apiKey) {
		$this->baseUrl = rtrim($baseUrl, '/');
		$this->apiKey = $apiKey;

		$this->client = new CurlWrapper();
		$this->client->addCustomHeaders([
			'Client-Key: ' . $apiKey,
			'Accept: application/json',
			'Content-Type: application/json',
		], false);
	}

	public function get(string $uri, array $params = []): ?array {
		$url = $this->buildUrl($uri, $params);
		$response = $this->client->curlGetPage($url);
		ExternalRequestLogEntry::logRequest('chilifresh.chilipac', 'GET', $url, [], '', $this->client->getResponseCode(), $response, ['Client-Key' => $this->apiKey]);
		return $this->decode($response);
	}

	public function post(string $uri, array $data = []): ?array {
		$url = $this->buildUrl($uri);
		$response = $this->client->curlPostBodyData($url, $data);
		ExternalRequestLogEntry::logRequest('chilifresh.chilipac', 'POST', $url, [], json_encode($data), $this->client->getResponseCode(), $response, ['Client-Key' => $this->apiKey]);
		return $this->decode($response);
	}

	public function put(string $uri, array $data = []): ?array {
		$url = $this->buildUrl($uri);
		$body = json_encode($data);
		$response = $this->client->curlSendPage($url, 'PUT', $body);
		ExternalRequestLogEntry::logRequest('chilifresh.chilipac', 'PUT', $url, [], $body, $this->client->getResponseCode(), $response, ['Client-Key' => $this->apiKey]);
		return $this->decode($response);
	}

	public function delete(string $uri): ?array {
		$url = $this->buildUrl($uri);
		$response = $this->client->curlSendPage($url, 'DELETE');
		ExternalRequestLogEntry::logRequest('chilifresh.chilipac', 'DELETE', $url, [], '', $this->client->getResponseCode(), $response, ['Client-Key' => $this->apiKey]);
		return $this->decode($response);
	}

	private function buildUrl(string $uri, array $params = []): string {
		$url = $this->baseUrl . '/' . ltrim($uri, '/');
		if (!empty($params)) {
			$url .= '?' . http_build_query($params);
		}
		return $url;
	}

	private function decode(string|bool $response): ?array {
		if ($response === false || $response === '') {
			return null;
		}
		$decoded = json_decode($response, true);
		return is_array($decoded) ? $decoded : null;
	}

	public function login(string $barcode, string $pin): ?array {
		return $this->post('/patron/login', ['username' => $barcode, 'password' => $pin]);
	}

	/**
	 * Recently reviewed and recently rated bibs.
	 *
	 * @return array{reviews: array, ratings: array}|null null if the request failed.
	 */
	public function getRecentBibs(): ?array {
		$response = $this->get('bibs/recent');
		if ($response === null) {
			return null;
		}
		return [
			'reviews' => $response['data']['reviews']['data'] ?? [],
			'ratings' => $response['data']['ratings']['data'] ?? [],
		];
	}

	/**
	 * @return array|null List of bib records, or null if the request failed.
	 */
	public function getHighestRatedBibs(): ?array {
		$response = $this->get('bibs/highest_rated');
		return $response === null ? null : ($response['data'] ?? []);
	}

	/**
	 * @return array|null List of bib records, or null if the request failed.
	 */
	public function getTrendingBibs(): ?array {
		$response = $this->get('bibs/trending');
		return $response === null ? null : ($response['data'] ?? []);
	}

	/**
	 * The HTTP status of the most recent request. Lets callers tell apart the
	 * public booklist API's 404 (no such booklist) and 403 (not public).
	 */
	public function getResponseCode(): int {
		return (int)$this->client->getResponseCode();
	}

	/**
	 * A public booklist with all of its items. The API pages items 100 at a time,
	 * so walk the pages until they run out.
	 *
	 * @param string $booklistId
	 * @return array|null The booklist, or null if the request failed. Check
	 *                    getResponseCode() to tell a 404/403 from a transport error.
	 */
	public function getPublicBooklist(string $booklistId): ?array {
		$booklist = null;
		$items = [];
		$page = 1;
		//Guard against a runaway loop if the API ever stops reporting total_pages.
		$maxPages = 20;
		do {
			$params = ['count' => 100];
			if ($page > 1) {
				$params['page'] = $page;
			}
			$response = $this->get('public/booklist/' . rawurlencode($booklistId), $params);
			if ($response === null || empty($response['data'])) {
				return null;
			}
			if ($booklist === null) {
				$booklist = $response['data'];
			}
			$items = array_merge($items, $response['data']['items']['data'] ?? []);
			$totalPages = (int)($response['data']['items']['meta']['pagination']['total_pages'] ?? 1);
			$page++;
		} while ($page <= $totalPages && $page <= $maxPages);

		$booklist['items']['data'] = $items;
		return $booklist;
	}

	/**
	 * The booklist types available for the library (e.g. Booklist, Resource guide, Storytime).
	 *
	 * @return array|null List of ['id' => ..., 'name' => ...], or null if the request failed.
	 */
	public function getBooklistTypes(): ?array {
		$response = $this->get('booklist/types');
		return $response === null ? null : ($response['data'] ?? []);
	}

	/**
	 * Props for the connections-card Vue component, which lists booklists and users
	 * related to a search term. Shared by search results and record pages so both
	 * show the same labels.
	 *
	 * @param string $searchTerm The term connections are looked up for, e.g. the search query or a title.
	 * @param string $infoText Tooltip explaining what the card shows on this page.
	 * @return string|null The encoded props, or null when there is nothing to search for.
	 */
	public static function getConnectionsProps(string $searchTerm, string $infoText): ?string {
		if (empty($searchTerm)) {
			return null;
		}
		return json_encode([
			's' => $searchTerm,
			'title' => translate([
				'text' => 'Connections',
				'isPublicFacing' => true,
			]),
			'info' => $infoText,
			'booklistsTitle' => translate([
				'text' => 'User booklists having related items',
				'isPublicFacing' => true,
			]),
			'usersTitle' => translate([
				'text' => 'Users related to this item',
				'isPublicFacing' => true,
			]),
		], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
	}

	/** @var array Grouped work permanent id => ILS bib id, memoized for the request */
	private static array $bibMapCache = [];

	/**
	 * Map grouped work permanent ids to the ILS bib ids ChiliPAC identifies titles by.
	 * Results are memoized per request since several parts of a page ask for the same works.
	 *
	 * @param string[] $permanentIds Grouped work permanent ids.
	 * @return array Permanent id => bib id, omitting works with no primary identifier.
	 */
	public static function getBibMapForGroupedWorks(array $permanentIds): array {
		global $aspen_db;
		$permanentIds = array_unique(array_filter($permanentIds));
		$idsToLookUp = array_diff($permanentIds, array_keys(self::$bibMapCache));
		if (!empty($idsToLookUp) && isset($aspen_db)) {
			$idsToLookUp = array_values($idsToLookUp);
			$placeholders = implode(',', array_fill(0, count($idsToLookUp), '?'));
			$stmt = $aspen_db->prepare(
				"SELECT gw.permanent_id, gwpi.identifier
				FROM grouped_work gw
				JOIN grouped_work_primary_identifiers gwpi ON gw.id = gwpi.grouped_work_id
				WHERE gw.permanent_id IN ($placeholders)"
			);
			$stmt->execute($idsToLookUp);
			//Remember the misses too so we don't look them up again later in the request
			foreach ($idsToLookUp as $permanentId) {
				self::$bibMapCache[$permanentId] = null;
			}
			foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
				$bibId = $row['identifier'];
				if ($bibId !== null && $bibId !== '' && empty(self::$bibMapCache[$row['permanent_id']])) {
					self::$bibMapCache[$row['permanent_id']] = $bibId;
				}
			}
		}

		$bibMap = [];
		foreach ($permanentIds as $permanentId) {
			if (!empty(self::$bibMapCache[$permanentId])) {
				$bibMap[$permanentId] = self::$bibMapCache[$permanentId];
			}
		}
		return $bibMap;
	}

	/**
	 * Caps the batched ratings request. ISBNs cost roughly 25 bytes each once encoded into the
	 * query string, so this keeps it near 5KB even on a 100 result page, well inside the 8KB
	 * request line most servers allow. Results past the cap simply show no rating.
	 */
	const MAX_ISBNS_PER_PAGE = 200;

	/**
	 * Map grouped work permanent ids to their ISBNs, taken from the Solr documents a search
	 * already returned (isbn and primary_isbn are part of the searcher's field list, so this
	 * costs no extra query). ChiliFresh matches ratings by ISBN, and the primary one is listed
	 * first so it is the first candidate when picking a rating for the work.
	 *
	 * @param array $recordSet Solr documents from SearchObject::getResultRecordSet().
	 * @return array Permanent id => list of ISBNs, omitting works that have none.
	 */
	public static function getIsbnMapForRecordSet(array $recordSet): array {
		$isbnMap = [];
		$totalIsbns = 0;
		foreach ($recordSet as $doc) {
			if (empty($doc['id']) || $totalIsbns >= self::MAX_ISBNS_PER_PAGE) {
				continue;
			}
			$isbns = [];
			if (!empty($doc['primary_isbn'])) {
				$isbns[] = (string)$doc['primary_isbn'];
			}
			if (!empty($doc['isbn'])) {
				$additional = is_array($doc['isbn']) ? $doc['isbn'] : [$doc['isbn']];
				foreach ($additional as $isbn) {
					//Indexed ISBNs can carry a qualifier, e.g. "9780062208385 (pbk.)"
					$isbn = trim((string)$isbn);
					if (strpos($isbn, ' ') !== false) {
						$isbn = substr($isbn, 0, strpos($isbn, ' '));
					}
					if ($isbn !== '') {
						$isbns[] = $isbn;
					}
				}
			}
			$isbns = array_values(array_unique($isbns));
			if (!empty($isbns)) {
				$isbnMap[$doc['id']] = $isbns;
				$totalIsbns += count($isbns);
			}
		}
		return $isbnMap;
	}

	public static function forLibrary(): ?self {
		global $library;
		if (empty($library->chiliFreshSettingId) || $library->chiliFreshSettingId < 0) {
			return null;
		}
		require_once ROOT_DIR . '/sys/Enrichment/ChiliFreshSetting.php';
		$setting = new ChiliFreshSetting();
		$setting->id = $library->chiliFreshSettingId;
		if (!$setting->find(true) || !$setting->chiliPacEnabled || empty($setting->chiliPacApiKey)) {
			return null;
		}
		return new self('https://api.chilifresh.com/api', $setting->chiliPacApiKey);
	}
}
