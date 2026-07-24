<?php

require_once ROOT_DIR . '/services/Profile/Profile.php';

class Profile_Bookshelf extends Profile {
	function launch() : void {
		global $interface;
		$interface->assign('activeProfilePage', 'bookshelf');
		$this->display('bookshelf.tpl', 'My Bookshelf');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Profile/Home', 'My Profile');
		$breadcrumbs[] = new Breadcrumb('/Profile/Bookshelf', 'Bookshelf');
		return $breadcrumbs;
	}
}
