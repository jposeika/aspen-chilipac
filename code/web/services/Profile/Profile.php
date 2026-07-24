<?php

/**
 * Base class for the custom ChiliPAC profile pages (booklist and bookshelf management).
 * Mirrors the MyAccount layout: requires a logged in user and shows a left hand menu.
 */
abstract class Profile extends Action {
	protected bool $requireLogin = true;

	function __construct($isStandalonePage = false) {
		parent::__construct($isStandalonePage);

		global $interface;
		//These pages only exist when ChiliPAC is enabled for the library.
		if (empty($interface->getVariable('chiliPacEnabled'))) {
			$interface->assign('module', 'Error');
			$interface->assign('action', 'Handle404');
			require_once ROOT_DIR . '/services/Error/Handle404.php';
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		if ($this->requireLogin && !UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/services/MyAccount/Login.php';
			$loginAction = new MyAccount_Login($isStandalonePage);
			$loginAction->launch();
			exit();
		}
	}

	/**
	 * @param string $mainContentTemplate Name of the Smarty template for the main content of the page.
	 * @param string $pageTitle What to display in the html title tag; run through the translator.
	 * @param string|null $sidebar The sidebar template to display on the left.
	 * @param bool $translateTitle
	 */
	function display($mainContentTemplate, $pageTitle = 'My Profile', $sidebar = 'Profile/profile-sidebar.tpl', $translateTitle = true) {
		parent::display($mainContentTemplate, $pageTitle, $sidebar, $translateTitle);
	}
}
