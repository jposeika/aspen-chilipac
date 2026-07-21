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
