<?php

require_once ROOT_DIR . '/services/Profile/Profile.php';

class Profile_Bookshelf extends Profile {
	private string $shelf = 'completed';

	function launch() : void {
		global $interface;
		$interface->assign('activeProfilePage', 'bookshelf');

		$shelf = $_REQUEST['id'] ?? 'completed';
		if (!in_array($shelf, ['completed', 'in_progress', 'for_later'])) {
			$shelf = 'completed';
		}
		$this->shelf = $shelf;
		$interface->assign('chiliPacShelf', $shelf);

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

		$this->display('bookshelf.tpl', 'My Bookshelf');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Profile/Home', 'My Profile');
		$breadcrumbs[] = new Breadcrumb('/Profile/Bookshelf', 'Bookshelf');
		return $breadcrumbs;
	}
}
