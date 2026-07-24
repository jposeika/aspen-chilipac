<?php

require_once ROOT_DIR . '/services/Profile/Profile.php';

class Profile_Booklists extends Profile {
	function launch() : void {
		global $interface;
		$interface->assign('activeProfilePage', 'booklists');
		$this->display('booklists.tpl', 'My Booklists');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Profile/Home', 'My Profile');
		$breadcrumbs[] = new Breadcrumb('/Profile/Booklists', 'Booklists');
		return $breadcrumbs;
	}
}
