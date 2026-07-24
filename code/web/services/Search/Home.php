<?php

require_once ROOT_DIR . '/Action.php';

class Search_Home extends Action {

	function launch() {
		global $interface;
		global $library;
		/** @var Location $locationSingleton */ global $locationSingleton;
		global $timer;

		$accessibleBrowseCategories = 0;
		if($interface->getVariable('accessibleBrowseCategories')) {
			$accessibleBrowseCategories = $interface->getVariable('accessibleBrowseCategories');
		}

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
		$timer->logTime('Include search engine');

		$interface->assign('showBreadcrumbs', 0);

		$interface->assign('isLoggedIn', false);
		$numHiddenCategory = 0;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getActiveUserObj();
			$loggedInUser = $user->id;
			$interface->assign('isLoggedIn', true);
			$interface->assign('loggedInUser', $loggedInUser);
			require_once ROOT_DIR . '/sys/Browse/BrowseCategoryDismissal.php';
			$browseCategoryDismissals = new BrowseCategoryDismissal();
			$browseCategoryDismissals->userId = $loggedInUser;
			$browseCategoryDismissals->find();
			$numHiddenCategory = $browseCategoryDismissals->count();
			$interface->assign('numHiddenCategory', $numHiddenCategory);
		}

		// Load browse categories
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';

		// Get Location's Browse Categories if Location is set
		$activeLocation = $locationSingleton->getActiveLocation();
		/** @var BrowseCategory[] $browseCategories */
		$browseCategories = [];
		if ($activeLocation != null) {
			if($activeLocation->getBrowseCategoryGroup()) {
				if($accessibleBrowseCategories == '1') {
					$browseCategories = $this->getInitialBrowseCategoryFeed($activeLocation->getBrowseCategoryGroup()->getBrowseCategories());
				} else {
					$browseCategories = $this->getBrowseCategories($activeLocation->getBrowseCategoryGroup()->getBrowseCategories());
				}
				$interface->assign('browseCategoryRatingsMode', $activeLocation->getBrowseCategoryGroup()->browseCategoryRatingsMode);
			}
		} else {
			if($library->getBrowseCategoryGroup()) {
				if($accessibleBrowseCategories == '1') {
					$browseCategories = $this->getInitialBrowseCategoryFeed($library->getBrowseCategoryGroup()->getBrowseCategories());
				} else {
					$browseCategories = $this->getBrowseCategories($library->getBrowseCategoryGroup()->getBrowseCategories());
				}
				$interface->assign('browseCategoryRatingsMode', $library->getBrowseCategoryGroup()->browseCategoryRatingsMode);
			}
		}

		$numBrowseCategories = count($browseCategories);
		$interface->assign('showBrowseContent', true);

		// Get All Browse Categories if Location & Library had none set
		if (empty($browseCategories)) {
			$interface->assign('showBrowseContent', false);
			$interface->assign('allBrowseCategoriesAreHidden', false);
			if (UserAccount::isLoggedIn()) {
				if ($numHiddenCategory > 0 && ($numBrowseCategories == $numHiddenCategory)) {
					$interface->assign('allBrowseCategoriesAreHidden', true);
				}
			}
		}

		$interface->assign('browseCategories', $browseCategories);

		//Set a Default Browse Mode
		if (count($browseCategories) > 0) {
			require_once ROOT_DIR . '/services/Browse/AJAX.php';
			$browseAJAX = new Browse_AJAX();
			$browseAJAX->setBrowseMode(); // set default browse mode in the case that the user hasn't chosen one.
		}
		if (!$interface->get_template_vars('browseMode')) {
			$interface->assign('browseMode', 'covers'); // fail safe: if no browseMode is set at all, go with covers
		}

		if (!empty($interface->getVariable('chiliPacEnabled'))) {
			$this->loadChiliPacCarousels();
		}

		$interface->assign('activeMenuOption', 'home');
		$this->display('home.tpl', 'Catalog Home', '');
	}

	private function loadChiliPacCarousels(): void {
		global $interface;

		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$recordUrlComponent = 'Record';
		$recordSource = 'ils';
		if ($indexingProfile->find(true)) {
			$recordUrlComponent = $indexingProfile->recordUrlComponent;
			$recordSource = $indexingProfile->name;
		}
		$interface->assign('chiliPacRecordUrlComponent', $recordUrlComponent);
		$interface->assign('chiliPacRecordSource', $recordSource);

		//The recent endpoint returns two lists (reviews and ratings); the others return a single list.
		$recentBibs = $this->getChiliPacCarouselData('recent', 'bibs/recent', function ($response) {
			return [
				'reviews' => $response['data']['reviews']['data'] ?? [],
				'ratings' => $response['data']['ratings']['data'] ?? [],
			];
		});
		if ($recentBibs !== null) {
			$interface->assign('chiliPacRecentReviews', $recentBibs['reviews']);
			$interface->assign('chiliPacRecentRatings', $recentBibs['ratings']);
		}

		$highestRated = $this->getChiliPacCarouselData('highest_rated', 'bibs/highest_rated', function ($response) {
			return ['items' => $response['data'] ?? []];
		});
		if ($highestRated !== null) {
			$interface->assign('chiliPacHighestRated', $highestRated['items']);
		}

		$trending = $this->getChiliPacCarouselData('trending', 'bibs/trending', function ($response) {
			return ['items' => $response['data'] ?? []];
		});
		if ($trending !== null) {
			$interface->assign('chiliPacTrending', $trending['items']);
		}
	}

	/**
	 * Fetch, trim, filter and cache the bibs for one ChiliPAC carousel endpoint.
	 * $extractor receives the decoded response and returns a map of section name => raw bib array.
	 *
	 * @return array|null Map of section name => trimmed/filtered bibs, or null if the API is unavailable.
	 */
	private function getChiliPacCarouselData(string $cacheSuffix, string $endpoint, callable $extractor): ?array {
		global $library;
		global $memCache;

		$cacheKey = 'chilipac_' . $cacheSuffix . '_bibs_' . $library->chiliFreshSettingId;
		$data = $memCache->get($cacheKey);
		if ($data === false) {
			require_once ROOT_DIR . '/sys/Enrichment/ChilipacApi.php';
			$chiliPacApi = ChilipacApi::forLibrary();
			if ($chiliPacApi === null) {
				return null;
			}
			$response = $chiliPacApi->get($endpoint);
			if ($response === null) {
				return null;
			}
			$data = [];
			$allBibIds = [];
			foreach ($extractor($response) as $section => $bibs) {
				$data[$section] = $this->trimChiliPacBibs($bibs);
				$allBibIds = array_merge($allBibIds, array_column($data[$section], 'bib_id'));
			}
			//Filter out bibs that do not exist in this catalog so we never link to invalid records
			$knownBibIds = $this->getKnownBibIds($allBibIds);
			foreach ($data as $section => $bibs) {
				$data[$section] = array_values(array_filter($bibs, function ($bib) use ($knownBibIds) {
					return isset($knownBibIds[$bib['bib_id']]);
				}));
			}
			$memCache->set($cacheKey, $data, 2 * 60 * 60);
		}
		return $data;
	}

	private function getKnownBibIds(array $bibIds): array {
		global $aspen_db;
		$bibIds = array_values(array_unique($bibIds));
		if (empty($bibIds) || !isset($aspen_db)) {
			return [];
		}
		$placeholders = implode(',', array_fill(0, count($bibIds), '?'));
		$stmt = $aspen_db->prepare("SELECT DISTINCT identifier FROM grouped_work_primary_identifiers WHERE identifier IN ($placeholders)");
		$stmt->execute(array_map('strval', $bibIds));
		return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
	}

	private function trimChiliPacBibs(array $bibs): array {
		$trimmed = [];
		foreach ($bibs as $bib) {
			if (empty($bib['bib_id'])) {
				continue;
			}
			$trimmed[] = [
				'bib_id' => $bib['bib_id'],
				'title' => $bib['title'] ?? '',
				'author' => $bib['author'] ?? '',
				'isbn' => $bib['isbn'] ?? '',
			];
		}
		return $trimmed;
	}


	/**
	 * @param BrowseCategoryGroup|null $localBrowseCategories
	 * @return BrowseCategory[]
	 */
	public function getBrowseCategories($localBrowseCategories = null) {
		$user = UserAccount::getActiveUserObj();

		$browseCategories = [];
		$specifiedCategory = isset($_REQUEST['browseCategory']);
		$specifiedSubCategory = $specifiedCategory && isset($_REQUEST['subCategory']); // make a specified main browse category required
		if ($localBrowseCategories) {
			$first = key($localBrowseCategories); // get key of first category
			foreach ($localBrowseCategories as $index => $localBrowseCategory) {
				$browseCategory = new BrowseCategory();
				$browseCategory->id = $localBrowseCategory->browseCategoryId;
				$browseCategory->find(true);
				// Only Show the Recommended for You browse category if the user is logged in and has rated titles
				if ($browseCategory->isValidForDisplay()) {
					$browseCategories[] = clone($browseCategory);
					if (($specifiedCategory && $_REQUEST['browseCategory'] == $browseCategory->textId) // A category has been selected through URL parameter
						|| (!$specifiedCategory && $index == $first) // Or default to selecting the first browse category
					) {
						$this->assignBrowseCategoryInformation($browseCategory, $specifiedSubCategory);
					}
				}
			}
		} /*else {
			// get All BrowseCategories
			$browseCategory = new BrowseCategory();
			$browseCategory->orderBy('numTitlesClickedOn');
			$browseCategory->limit(0, 20);
			$browseCategory->find();
			while ($browseCategory->fetch()) {
				//Do not use the browse category if it is a subcategory of any other category
				$subCategoryInfo = new SubBrowseCategories();
				$subCategoryInfo->subCategoryId = $browseCategory->id;
				$subCategoryInfo->find();
				if ($subCategoryInfo->getNumResults() > 0) {
					continue;
				}

//				$browseCategory->getSubCategories(); // add subcategory information to the object

				if ($browseCategory->isValidForDisplay()) {
					$browseCategories[] = clone($browseCategory);
				}

				if ($specifiedCategory && $_REQUEST['browseCategory'] == $browseCategory->textId) {
					$this->assignBrowseCategoryInformation($browseCategory, $specifiedSubCategory);
				}
			}
		}*/
		return $browseCategories;
	}

	/**
	 * @param BrowseCategory $browseCategory
	 * @param bool $specifiedSubCategory
	 */
	private function assignBrowseCategoryInformation(BrowseCategory $browseCategory, bool $specifiedSubCategory): void {
		global $interface;
		$selectedBrowseCategory = clone($browseCategory);
		$interface->assign('selectedBrowseCategory', $selectedBrowseCategory);
		if ($specifiedSubCategory) {
			$selectedBrowseCategory->getSubCategories();

			$validSubCategory = false;
			$subCategories = [];
			/** @noinspection PhpUndefinedFieldInspection */
			/** @var SubBrowseCategories $subCategory */
			foreach ($selectedBrowseCategory->subBrowseCategories as $subCategory) {
				// Get Needed Info about sub-category
				if ($subCategory instanceof UserList) {
					$subCategories[] = [
						'label' => $subCategory->title,
						'textId' => $subCategory->id,
					];
				} elseif ($subCategory instanceof SearchEntry) {
					$subCategories[] = [
						'label' => $subCategory->title,
						'textId' => $subCategory->id,
					];
				} else {
					$temp = new BrowseCategory();
					$temp->get($subCategory->subCategoryId);
					if ($temp->isValidForDisplay()) {
						if ($temp->textId == $_REQUEST['subCategory']) {
							$validSubCategory = true;
						}
						$subCategories[] = [
							'label' => $temp->label,
							'textId' => $temp->textId,
						];
					}
				}
			}
			if ($validSubCategory) {
				$interface->assign('subCategoryTextId', $_REQUEST['subCategory']);
				$interface->assign('subCategories', $subCategories);
			}
		}
	}

	/**
	 * @param BrowseCategoryGroupEntry[]|null $localBrowseCategories
	 * @return BrowseCategory[]
	 */
	private function getInitialBrowseCategoryFeed(array $localBrowseCategories = null): array {
		// Build basic list of categories, marking those with subcategories.
		require_once ROOT_DIR . '/services/API/SearchAPI.php';
		$searchAPI = new SearchAPI();
		$browseCategories = [];
		if ($localBrowseCategories) {
			foreach ($localBrowseCategories as $localBrowseCategory) {
				$browseCategory = new BrowseCategory();
				$browseCategory->id = $localBrowseCategory->browseCategoryId;
				$browseCategory->find(true);
				if ($browseCategory->isValidForDisplay()) {
					$textId = $browseCategory->textId;
					$subCatResult = $searchAPI->getSubCategories($textId);
					$hasSubcategories = !empty($subCatResult['subCategories']);
					$subcategoryCount = $hasSubcategories ? count($subCatResult['subCategories']) : 0;
					// Only set searchUrl to '#' for categories without subcategories or with exactly one subcategory
					// because it will become the only search URL.
					// For parent categories with multiple subcategories, set empty string to prevent dead-end links.
					$searchUrl = (!$hasSubcategories || $subcategoryCount == 1) ? '#' : '';
					$browseCategories[] = [
						'textId' => $textId,
						'label' => $browseCategory->label,
						'searchUrl' => $searchUrl,
						'hasSubcategories' => $hasSubcategories,
					];
				}
			}
		}
		return $browseCategories;
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
