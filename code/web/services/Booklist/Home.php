<?php

require_once ROOT_DIR . '/Action.php';

/**
 * Public facing page for a single ChiliPAC booklist, e.g. /Booklist/4mMAn.
 * The booklist itself is loaded client side from the ChiliPAC API (which returns
 * a 404 for unknown booklists and a 403 for booklists that are not public), so
 * this action only wraps it in Aspen's layout and supplies the record source and
 * url component needed to link the items back into the catalog.
 */
class Booklist_Home extends Action {
	private string $booklistId = '';

	function __construct($isStandalonePage = false) {
		parent::__construct($isStandalonePage);

		global $interface;
		//This page only exists when ChiliPAC is enabled for the library.
		if (empty($interface->getVariable('chiliPacEnabled'))) {
			$this->handle404();
		}
	}

	function launch() : void {
		global $interface;

		$this->booklistId = $_REQUEST['id'] ?? '';
		if ($this->booklistId === '') {
			$this->handle404();
		}

		//The indexing profile provides the source for Aspen cover URLs (e.g. polaris:12345)
		//and the record URL component for links to the record details page.
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$recordSource = 'ils';
		$recordUrlComponent = 'Record';
		if ($indexingProfile->find(true)) {
			$recordSource = $indexingProfile->name;
			$recordUrlComponent = $indexingProfile->recordUrlComponent;
		}

		$interface->assign('publicBooklistProps', json_encode([
			'id' => $this->booklistId,
			'recordSource' => $recordSource,
			'recordUrlComponent' => $recordUrlComponent,
			//Number of items to request per page from the public booklist API.
			'count' => 10,
		], JSON_HEX_APOS));

		$this->display('home.tpl', 'Booklist', 'Booklist/booklist-sidebar.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Booklists/Search', 'Booklists');
		//The name is only known once the page loads the booklist, so show a
		//generic label that the Vue component replaces.
		$breadcrumbs[] = new Breadcrumb('/Booklist/' . urlencode($this->booklistId), 'Booklist');
		return $breadcrumbs;
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
}
