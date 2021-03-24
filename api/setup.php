<?php
if(!defined('CLASS_PATH'))
	define('CLASS_PATH', dirname(__DIR__) . '/etc/class/');
require_once CLASS_PATH . 'api.php';

/**
 * Handler for setup API requests.
 * @author misterhaan
 */
class SetupApi extends Api {
	/**
	 * Get the current setup level.
	 */
	public static function currentLevel() {
		require_once CLASS_PATH . 'url.php';
		$docroot = Url::DocRoot();
		$result = (object)['level' => -4];
		if(!file_exists(dirname($docroot) . '/.lanaKeys.php'))
			$result->stepData = "File not found";
		else {
			require_once(dirname($docroot) . '/.lanaKeys.php');
			if(!class_exists('KeysDB') || !class_exists('KeysTwitch'))
				$result->stepData = "Class not defined";
			elseif(!defined('KeysDB::Host') || !defined('KeysDB::Name') || !defined('KeysDB::User') || !defined('KeysDB::Pass')
				|| !KeysDB::Host || !KeysDB::Name || !KeysDB::User || !KeysDB::Pass
				|| !defined('KeysTwitch::ClientId') || !defined('KeysTwitch::ClientSecret') || !KeysTwitch::ClientId || !KeysTwitch::ClientSecret)
				$result->stepData = "Class incomplete";
			else {
				$result->level = -3;
				$db = @new mysqli(KeysDB::Host, KeysDB::User, KeysDB::Pass, KeysDB::Name);
				if(!$db || $db->connect_errno)
					$result->stepData = [
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
									require_once CLASS_PATH . 'version.php';
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
	 * Configure the database and external API connections.
	 */
	protected static function POST_configureConnections() {
		if(isset($_POST['host'], $_POST['name'], $_POST['user'], $_POST['pass'], $_POST['twitchId'], $_POST['twitchSecret'])
			&& ($host = trim($_POST['host'])) && ($name = trim($_POST['name']))
			&& ($user = trim($_POST['user'])) && ($pass = $_POST['pass'])
			&& ($twitchId = trim($_POST['twitchId'])) && ($twitchSecret = trim($_POST['twitchSecret']))) {
			require_once CLASS_PATH . 'url.php';
			$path = dirname(Url::DocRoot()) . '/.lanaKeys.php';
			$contents = '<?php
class KeysDB {
	const Host = \'' . addslashes($host) . '\';
	const Name = \'' . addslashes($name) . '\';
	const User = \'' . addslashes($user) . '\';
	const Pass = \'' . addslashes($pass) . '\';
}
class KeysTwitch {
	const ClientId = \'' . addslashes($twitchId) . '\';
	const ClientSecret = \'' . addslashes($twitchSecret) . '\';
}';
			if($fh = fopen($path, 'w')) {
				fwrite($fh, $contents);
				self::Success(array_merge(self::currentLevel(), ['path' => $path, 'saved' => true]));
			} else
				self::Success(['path' => $path, 'saved' => false, 'message' => error_get_last()['message'], 'template' => self::GetKeysTemplate()]);
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
			'table/config', 'table/profile', 'table/player', 'table/account',
			'table/cookie', 'table/email'
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
	 * Get the contents of the keys template file.
	 * @return string Contents of the keys template file
	 */
	private static function GetKeysTemplate() {
		return file_get_contents('../etc/lanaKeys.template.php');
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
