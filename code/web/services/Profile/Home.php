<?php

require_once ROOT_DIR . '/services/Profile/Profile.php';

class Profile_Home extends Profile {
	function launch() : void {
		global $interface;
		$interface->assign('activeProfilePage', 'home');
		$this->display('home.tpl', 'My Profile');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Profile/Home', 'My Profile');
		return $breadcrumbs;
	}
}
