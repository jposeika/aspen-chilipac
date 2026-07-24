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
			$interface->assign('chiliPacRecordSource', $recordSource);
			$interface->assign('chiliPacRecordUrlComponent', $recordUrlComponent);
			$this->display('booklist.tpl', 'My Booklist');
		} else {
			$this->display('booklists.tpl', 'My Booklists');
		}
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
