<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Enrichment/ChilipacApi.php';

/**
 * Generates a PDF of a public ChiliPAC booklist, e.g. /Booklist/4mMAn/DownloadPDF.
 *
 * The booklist is fetched server side (the public API needs no patron auth) and
 * rendered to HTML with Smarty, which dompdf converts to a PDF. Covers are
 * embedded as data URIs so dompdf never needs remote file access enabled.
 */
class Booklist_DownloadPDF extends Action {
	//Roughly how many characters of the description fit on one header line, and
	//how many lines the repeating header is allowed to give it.
	const DESCRIPTION_CHARS_PER_LINE = 95;
	const DESCRIPTION_MAX_LINES = 4;
	//The same for the booklist name, which is set in a larger face.
	const NAME_CHARS_PER_LINE = 70;
	const NAME_MAX_LINES = 3;
	//How many covers in a row may fail before the rest of the list gives up on them.
	const MAX_COVER_FAILURES = 3;

	function __construct($isStandalonePage = false) {
		parent::__construct($isStandalonePage);

		global $interface;
		if (empty($interface->getVariable('chiliPacEnabled'))) {
			$this->handle404();
		}
	}

	//Consecutive cover fetch failures, so a dead cover host is only tried so often.
	private int $coverFetchFailures = 0;

	function launch() : void {
		global $interface;

		$booklistId = $_REQUEST['id'] ?? '';
		if ($booklistId === '') {
			$this->handle404();
		}

		$chiliPacApi = ChilipacApi::forLibrary();
		if ($chiliPacApi === null) {
			$this->handle404();
		}

		$booklist = $chiliPacApi->getPublicBooklist($booklistId);
		if ($booklist === null) {
			//403 means the booklist exists but has not been shared publicly; anything
			//else (404 included) is indistinguishable from a missing booklist here.
			if ($chiliPacApi->getResponseCode() == 403) {
				$this->handle403();
			}
			$this->handle404();
		}

		$description = trim($booklist['description'] ?? '');
		$author = trim($booklist['author']['data']['nickname'] ?? '');
		$interface->assign('booklistName', $booklist['name'] ?? '');
		$interface->assign('booklistDescription', $this->truncateDescription($description));
		$interface->assign('booklistAuthor', $author);
		$interface->assign('booklistItems', $this->getItemsForDisplay($booklist['items']['data'] ?? []));
		//A fixed position header repeats on every page, so the space reserved for it
		//has to be a constant. Size it to what is actually being shown.
		$this->assignHeaderMetrics($booklist['name'] ?? '', $description, $author);

		$this->renderPdf($interface->fetch('Booklist/pdf.tpl'), $booklist['name'] ?? 'booklist');
	}

	/**
	 * Reduce the API's items to what the PDF shows, dropping anything without a
	 * title and resolving each cover to an embeddable data URI.
	 */
	private function getItemsForDisplay(array $items) : array {
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$recordSource = $indexingProfile->find(true) ? $indexingProfile->name : 'ils';

		$displayItems = [];
		foreach ($items as $item) {
			$title = trim($item['data']['title'] ?? '');
			if ($title === '') {
				continue;
			}
			$displayItems[] = [
				'title' => $title,
				'author' => trim($item['data']['author'] ?? ''),
				'cover' => $this->getCoverDataUri($item, $recordSource),
			];
		}
		return $displayItems;
	}

	/**
	 * A data URI for the item's cover, or an empty string when no cover is
	 * available. Uses the same covers as the website so the PDF matches it.
	 */
	private function getCoverDataUri(array $item, string $recordSource) : string {
		global $configArray;

		$bibId = ($item['type'] ?? '') == 'item' && !empty($item['data']['bib_id']) ? (string)$item['data']['bib_id'] : '';
		if ($bibId !== '') {
			//Mirrors the cache naming in BookCoverProcessor: {coverPath}/{size}/{type}_{id}.png
			$cacheName = preg_replace('/[^a-zA-Z0-9_.-]/', '', $recordSource . '_' . $bibId);
			$coverFile = $configArray['Site']['coverPath'] . '/medium/' . $cacheName . '.png';
			if (is_readable($coverFile)) {
				$contents = @file_get_contents($coverFile);
				if ($contents !== false) {
					return 'data:image/png;base64,' . base64_encode($contents);
				}
			}
		}

		//Either the item is not in the catalog, or Aspen has not cached its cover
		//yet because the booklist page was never viewed.
		return $this->fetchCoverDataUri($item['data']['image'] ?? '');
	}

	/**
	 * Fetch the cover the ChiliPAC API supplied for the item.
	 *
	 * Aspen's own bookcover.php is not usable here: BookCoverProcessor writes
	 * response headers and exits outright on a provider redirect, so it cannot be
	 * called in process, and requesting the site over HTTP depends on the server
	 * being able to reach its own public address, which it often cannot.
	 */
	private function fetchCoverDataUri(string $imageUrl) : string {
		if ($imageUrl === '' || !preg_match('~^https?://~i', $imageUrl)) {
			return '';
		}
		//Once the cover host stops answering, stop asking: a booklist can hold
		//hundreds of items and each attempt costs a timeout.
		if ($this->coverFetchFailures >= self::MAX_COVER_FAILURES) {
			return '';
		}

		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		$curl = new CurlWrapper();
		//One slow cover should not hold up the whole PDF.
		$curl->setTimeout(5);
		$response = $curl->curlGetPage($imageUrl);
		if ($response === false || $response === '') {
			$this->coverFetchFailures++;
			return '';
		}

		//Cover services answer with an error page rather than an image when they have
		//nothing, so make sure what came back really is one.
		$imageInfo = @getimagesizefromstring($response);
		if ($imageInfo === false || empty($imageInfo['mime'])) {
			$this->coverFetchFailures++;
			return '';
		}
		$this->coverFetchFailures = 0;
		return 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($response);
	}

	/**
	 * Long descriptions repeat on every page, so cap them rather than letting the
	 * header eat the page.
	 */
	private function truncateDescription(string $description) : string {
		$maxLength = self::DESCRIPTION_CHARS_PER_LINE * self::DESCRIPTION_MAX_LINES;
		if (strlen($description) <= $maxLength) {
			return $description;
		}
		return rtrim(substr($description, 0, $maxLength - 1)) . '…';
	}

	/**
	 * dompdf positions fixed elements relative to the top of the page content
	 * area, and they do not push the content down. So the header is pulled up
	 * into the top page margin, and that margin has to be big enough to hold it
	 * on every page. All three measurements are in millimetres.
	 */
	private function assignHeaderMetrics(string $name, string $description, string $author) : void {
		global $interface;

		//Space between the top of the paper and the header.
		$topMargin = 10;
		//Space between the header's bottom rule and the first row of items.
		$gap = 5;

		$nameLines = min(
			max((int)ceil(strlen($name) / self::NAME_CHARS_PER_LINE), 1),
			self::NAME_MAX_LINES
		);
		$headerHeight = 3 + ($nameLines * 6);
		if ($description !== '') {
			$lines = min(
				(int)ceil(strlen($description) / self::DESCRIPTION_CHARS_PER_LINE),
				self::DESCRIPTION_MAX_LINES
			);
			$headerHeight += 3 + ($lines * 5);
		}
		if ($author !== '') {
			$headerHeight += 5;
		}

		$interface->assign('headerHeight', $headerHeight);
		$interface->assign('pageMarginTop', $topMargin + $headerHeight + $gap);
		//How far above the content area the header has to sit, as a positive number.
		$interface->assign('headerOffset', $headerHeight + $gap);
	}

	/**
	 * dompdf is vendored (there is no composer in the web root), so its classes
	 * come from composer's autoloader. That has to run ahead of aspen_autoloader,
	 * which ends in a bare require_once and so fatals on any namespaced class it
	 * does not recognise instead of passing it along.
	 */
	private function loadDompdf() : void {
		if (class_exists('\Dompdf\Dompdf', false)) {
			return;
		}
		$loader = require ROOT_DIR . '/dompdf/vendor/autoload.php';
		$loader->unregister();
		$loader->register(true);
	}

	private function renderPdf(string $html, string $title) : void {
		$this->loadDompdf();

		$options = new \Dompdf\Options();
		//Everything the PDF needs is inlined, so leave remote access off.
		$options->setIsRemoteEnabled(false);
		$options->setTempDir(sys_get_temp_dir());
		$options->setFontCache(sys_get_temp_dir());
		$options->setDefaultFont('DejaVu Sans');

		$dompdf = new \Dompdf\Dompdf($options);
		$dompdf->setPaper('letter', 'portrait');
		$dompdf->loadHtml($html, 'UTF-8');
		$dompdf->render();

		$dompdf->stream($this->getFileName($title) . '.pdf', ['Attachment' => true]);
		die();
	}

	/**
	 * The booklist name as a lower case, underscore separated file name.
	 */
	private function getFileName(string $title) : string {
		$fileName = strtolower(preg_replace('/[^a-zA-Z0-9 _.-]/', '', $title));
		//Collapse the runs of whitespace left behind by stripping punctuation.
		$fileName = trim(preg_replace('/[\s_]+/', '_', trim($fileName)), '_');
		return $fileName === '' ? 'booklist' : $fileName;
	}

	private function handle403() : void {
		global $interface;
		$interface->assign('module', 'Error');
		$interface->assign('action', 'Handle403');
		require_once ROOT_DIR . '/services/Error/Handle403.php';
		$actionClass = new Error_Handle403();
		$actionClass->launch();
		die();
	}

	private function handle404() : void {
		global $interface;
		$interface->assign('module', 'Error');
		$interface->assign('action', 'Handle404');
		require_once ROOT_DIR . '/services/Error/Handle404.php';
		$actionClass = new Error_Handle404();
		$actionClass->launch();
		die();
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
