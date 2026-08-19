<?php /** @noinspection PhpMissingFieldTypeInspection */

class ChiliFreshSetting extends DataObject
{
	public $__table = 'chilifresh_settings';    // table name
	public $id;
	public $name;
	public $enabled;
	public $genericArtCode;
	public $chiliPacEnabled;
	public $chiliPacApiKey;
	public $chiliPacReviewsEnabled;

	private $_libraries;

	function getEncryptedFieldNames(): array {
		return ['chiliPacApiKey'];
	}

	public function getNumericColumnNames(): array {
		return [
			'enabled',
			'chiliPacEnabled',
			'chiliPacReviewsEnabled',
		];
	}

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array
	{
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name to identify these settings',
				'maxlength' => 50,
			],
			'enabled' => [
				'property' => 'enabled',
				'type' => 'checkbox',
				'label' => 'Cover Art Integration Enabled',
				'description' => 'Whether or not ChiliFresh cover art integration is enabled',
				'default' => 1,
			],
			'genericArtCode' => [
				'property' => 'genericArtCode',
				'type' => 'text',
				'label' => 'Generic Art Code',
				'description' => 'Optional code supplied by ChiliFresh to specify fallback image to use',
				'maxlength' => 255,
				'required' => false,
			],
			'chiliPacEnabled' => [
				'property' => 'chiliPacEnabled',
				'type' => 'checkbox',
				'label' => 'ChiliPAC Enabled',
				'description' => 'Whether or not ChiliFresh ChiliPAC integration is enabled',
				'default' => 0,
			],
			'chiliPacApiKey' => [
				'property' => 'chiliPacApiKey',
				'type' => 'storedPassword',
				'label' => 'ChiliPAC API Key',
				'description' => 'The API key supplied by ChiliFresh for ChiliPAC',
				'required' => false,
				'hideInLists' => true,
			],
			'chiliPacReviewsEnabled' => [
				'property' => 'chiliPacReviewsEnabled',
				'type' => 'checkbox',
				'label' => 'ChiliFresh Ratings &amp; Reviews',
				'description' => 'Whether or not ChiliFresh ratings and reviews replace Aspen\'s own user ratings and user reviews. Ratings and reviews must still be enabled in Grouped Work Display Settings ("Enable User Ratings" and "Enable User Reviews") for either version to be shown. Syndicated and GoodReads reviews are not affected.',
				'default' => 0,
				'hideInLists' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->chiliFreshSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	/**
	 * Whether ChiliFresh ratings and reviews take the place of Aspen's own user ratings and reviews
	 * for the active library. Used both when rendering and when guarding the rating/review endpoints.
	 */
	public static function reviewsReplaceAspen() : bool {
		global $library;
		if (empty($library->chiliFreshSettingId) || $library->chiliFreshSettingId < 0) {
			return false;
		}
		$setting = new ChiliFreshSetting();
		$setting->id = $library->chiliFreshSettingId;
		if (!$setting->find(true)) {
			return false;
		}
		return !empty($setting->chiliPacEnabled) && !empty($setting->chiliPacReviewsEnabled);
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->chiliFreshSettingId != $this->id) {
						$library->chiliFreshSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->chiliFreshSettingId == $this->id) {
						$library->chiliFreshSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}
