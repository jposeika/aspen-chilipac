<?php

require_once ROOT_DIR . '/services/Profile/Profile.php';

class Profile_Booklists extends Profile {
	private string $booklistId = '';

	function launch() : void {
		global $interface;
		$interface->assign('activeProfilePage', 'booklists');

		$this->booklistId = $_REQUEST['id'] ?? '';
		if ($this->booklistId !== '') {
			$interface->assign('chiliPacBooklistId', $this->booklistId);
			$interface->assign('chiliPacRecordSource', $this->getRecordSource());
			$this->display('booklist.tpl', 'My Booklist');
		} else {
			$this->display('booklists.tpl', 'My Booklists');
		}
	}

	/**
	 * The indexing profile name used to build Aspen cover URLs (e.g. polaris:12345).
	 */
	private function getRecordSource(): string {
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		if ($indexingProfile->find(true)) {
			return $indexingProfile->name;
		}
		return 'ils';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Profile/Home', 'My Profile');
		$breadcrumbs[] = new Breadcrumb('/Profile/Booklists', 'Booklists');
		if ($this->booklistId !== '') {
			$breadcrumbs[] = new Breadcrumb('/Profile/Booklists/' . urlencode($this->booklistId), 'Booklist');
		}
		return $breadcrumbs;
	}
}
