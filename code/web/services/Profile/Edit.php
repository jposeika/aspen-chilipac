<?php

require_once ROOT_DIR . '/services/Profile/Profile.php';

class Profile_Edit extends Profile {
	function launch() : void {
		global $interface;
		$interface->assign('activeProfilePage', 'edit');
		$this->display('edit.tpl', 'Edit Profile');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Profile/Home', 'My Profile');
		$breadcrumbs[] = new Breadcrumb('/Profile/Edit', 'Edit Profile');
		return $breadcrumbs;
	}
}
