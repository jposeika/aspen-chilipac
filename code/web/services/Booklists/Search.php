<?php

require_once ROOT_DIR . '/Action.php';

/**
 * Public facing search page for ChiliPAC booklists. Shows the booklist filter
 * on the left hand sidebar; the main content area is populated separately.
 */
class Booklists_Search extends Action {

	function __construct($isStandalonePage = false) {
		parent::__construct($isStandalonePage);

		global $interface;
		//This page only exists when ChiliPAC is enabled for the library.
		if (empty($interface->getVariable('chiliPacEnabled'))) {
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . '/services/Error/Handle404.php';
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
	}

	function launch() : void {
		global $interface;

		//Number of results to request per page from the booklist/search API.
		$interface->assign('booklistResultsProps', json_encode(['count' => 9], JSON_HEX_APOS));

		$this->display('search.tpl', 'Booklists', 'Booklists/search-sidebar.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Booklists/Search', 'Booklists');
		return $breadcrumbs;
	}
}
