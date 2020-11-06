<?php
require_once dirname(__DIR__) . '/etc/class/api.php';

/**
 * Handler for setup API requests.
 * @author misterhaan
 */
class SetupApi extends Api {
	/**
	 * Get the current setup level.
	 */
	public static function currentLevel() {
		$result = (object)['level' => -4];
		if(!file_exists(dirname(DOCROOT) . '/.lanaKeys.php'))
			$result->stepData = "File not found";
		else {
			require_once(dirname(DOCROOT) . '/.lanaKeys.php');
			if(!class_exists('KeysDB'))
				$result->stepData = "Class not defined";
			elseif(!defined('KeysDB::HOST') || !defined('KeysDB::NAME') || !defined('KeysDB::USER') || !defined('KeysDB::PASS'))
				$result->stepData = "Class incomplete";
			else {
				$result->level = -3;
				$db = @new mysqli(KeysDB::HOST, KeysDB::USER, KeysDB::PASS, KeysDB::NAME);
				if(!$db || $db->connect_errno)
					$result->stepData = [
						// TODO:  don't send these up
						'name' => KeysDB::NAME,
						'user' => KeysDB::USER,
						'pass' => KeysDB::PASS,
						'error' => $db->connect_errno . ' ' . $db->connect_error
					];
				else {
					$result->level = -2;
					if($select = $db->prepare('select structureVersion from config limit 1'))
						if($select->execute()) {
							$config = new stdClass();
							if($select->bind_result($config->structureVersion))
								if($select->fetch()) {
									$result->level = -1;
									if($config->structureVersion < Version::Structure)
										$result->stepData = [
											'structureBehind' => Version::Structure - $config->structureVersion
										];
									else
										$result->level = 0;
								} else
									$result->stepData = 'Configuration data missing';
							else
								$result->stepData = $select->errno . ' ' . $select->error;
						} else
							$result->stepData = $select->errno . ' ' . $select->error;
					else
						$result->stepData = $db->errno . ' ' . $db->error;
				}
			}
		}
		return $result;
	}

	/**
	 * Get the current setup level.
	 */
	protected static function GET_level() {
		self::Success(self::currentLevel());
	}

	/**
	 * Configure the database connection.
	 */
	protected static function POST_configureDatabase() {
		if(isset($_POST['host'], $_POST['name'], $_POST['user'], $_POST['pass']) && ($host = trim($_POST['host']))
			&& ($name = trim($_POST['name'])) && ($user = trim($_POST['user'])) && ($pass = $_POST['pass'])) {
			$path = dirname(DOCROOT) . '/.lanaKeys.php';
			$contents = '<?php
class KeysDB {
	const HOST = \'' . addslashes($_POST['host']) . '\';
	const NAME = \'' . addslashes($_POST['name']) . '\';
	const USER = \'' . addslashes($_POST['user']) . '\';
	const PASS = \'' . addslashes($_POST['pass']) . '\';
}';
			if($fh = fopen($path, 'w')) {
				fwrite($fh, $contents);
				self::Success(array_merge(self::currentLevel(), ['path' => $path, 'saved' => true]));
			} else
				self::Success(['path' => $path, 'saved' => false, 'message' => error_get_last()['message'], 'contents' => $contents]);
		} else
			self::NeedMoreInfo('Parameters host, name, user, and pass are all required and cannot be blank.');
	}

	/**
	 * Install the database at the latest version.
	 */
	protected static function POST_installDatabase() {
		$db = self::RequireDatabase();
		// tables, views, routines; then alphabetical order.  if anything has
		// dependencies that come later, it comes after its last dependency.
		$files = [
			'table/config'
		];
		$db->autocommit(false);  // no partial database installations
		foreach($files as $file)
			self::RunQueryFile($file, $db);

		if($db->real_query('insert into config (structureVersion) values (' . +Version::Structure . ')')) {
			$db->commit();
			self::Success();
		} else
			self::DatabaseError('Error initializing configuration', $db);
	}

	/**
	 * Run all applicable upgrade scripts to bring the database up to the current version.
	 */
	protected static function POST_upgradeDatabase() {
		$db = self::RequireDatabaseWithConfig();
		$db->autocommit(false);  // each step should commit only if the entire step succeeds
		if($db->config->structureVersion < Version::Structure)
			self::UpgradeDatabaseStructure($db);
		self::Success();
	}

	/**
	 * Upgrade database structure and update the structure version.
	 * @param mysqli $db Database connection object.
	 */
	private static function UpgradeDatabaseStructure(mysqli $db) {
		// add future structure upgrades here (older ones need to go first)
	}

	/**
	 * Perform one step of a data structure upgrade.
	 * @param int $ver Structure version upgrading to (use a constant from StructureVersion)
	 * @param mysqli $db Database connection object
	 * @param string[] $queryfiles File subdirectory and name without extension for each query file to run
	 */
	private static function UpgradeDatabaseStructureStep(int $version, mysqli $db, string ...$queryfiles) {
		if($db->config->structureVersion < $version) {
			foreach($queryfiles as $file)
				self::RunQueryFile($file, $db);

			self::SetStructureVersion($version, $db);
			$db->commit();
		}
	}

	/**
	 * Load a query from a file and run it.
	 * @param string $filepath File subdirectory and name without extension
	 * @param mysqli $db Database connection object
	 */
	private static function RunQueryFile(string $filepath, mysqli $db) {
		$sql = trim(file_get_contents(dirname(__DIR__) . '/etc/db/' . $filepath . '.sql'));
		if(substr($filepath, 0, 12) == 'transition/') {  // transitions usually have more than one query
			if($db->multi_query($sql)) {
				while($db->next_result());  // these queries don't return results but we need to get past them to continue
				return;
			}
		} else {
			if($db->real_query($sql))
				return;
		}
		// if we haven't returned already, the query failed
		list($type, $name) = explode('/', $filepath);
		self::DatabaseError("Error creating $name $type", $db);
	}

	/**
	 * Sets the structure version to the provided value.  Use this after making
	 * database structure upgrades.
	 * @param int $ver Structure version to set (use a constant from StructureVersion)
	 * @param mysqli $db Database connection object
	 */
	private static function SetStructureVersion(int $ver, mysqli $db) {
		if($db->real_query('update config set structureVersion=' . +$ver . ' limit 1'))
			$db->config->structureVersion = +$ver;
		else
			self::DatabaseError("Error setting structure version to $ver", $db);
	}
}
SetupApi::Respond();
