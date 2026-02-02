<?php
if (!defined('CLASS_PATH'))
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
	public static function currentLevel(): object {
		require_once CLASS_PATH . 'url.php';
		$docroot = Url::DocRoot();
		$result = (object)['level' => -4];
		if (!file_exists(dirname($docroot) . '/.lanaKeys.php'))
			$result->stepData = "File not found";
		else {
			require_once(dirname($docroot) . '/.lanaKeys.php');
			if (!class_exists('KeysDB') || !class_exists('KeysTwitch') || !class_exists('KeysGoogle'))
				$result->stepData = "Class not defined";
			elseif (
				!defined('KeysDB::Host') || !defined('KeysDB::Name') || !defined('KeysDB::User') || !defined('KeysDB::Pass')
				|| !KeysDB::Host || !KeysDB::Name || !KeysDB::User || !KeysDB::Pass
				|| !defined('KeysTwitch::ClientId') || !defined('KeysTwitch::ClientSecret') || !KeysTwitch::ClientId || !KeysTwitch::ClientSecret
				|| !defined('KeysGoogle::ClientId') || !defined('KeysGoogle::ClientSecret') || !KeysGoogle::ClientId || !KeysGoogle::ClientSecret
			)
				$result->stepData = "Class incomplete";
			else {
				$result->level = -3;
				$db = @new mysqli(KeysDB::Host, KeysDB::User, KeysDB::Pass, KeysDB::Name);
				if (!$db || $db->connect_errno)
					$result->stepData = [
						'error' => $db->connect_errno . ' ' . $db->connect_error
					];
				else {
					$result->level = -2;
					try {
						$select = $db->prepare('select structureVersion from config limit 1');
						$select->execute();
						$select->bind_result($structureVersion);
						if ($select->fetch()) {
							$result->level = -1;
							require_once CLASS_PATH . 'version.php';
							if ($structureVersion < Version::Structure)
								$result->stepData = [
									'structureBehind' => Version::Structure - $structureVersion
								];
							else
								$result->level = 0;
						} else
							$result->stepData = 'Configuration data missing';
					} catch (mysqli_sql_exception $mse) {
						$result->stepData = $mse->getCode() . ' ' . $mse->getMessage();
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Get the current setup level.
	 */
	protected static function GET_level(): void {
		self::Success(self::currentLevel());
	}

	/**
	 * Configure the database and external API connections.
	 */
	protected static function POST_configureConnections(): void {
		if (
			!isset($_POST['host'], $_POST['name'], $_POST['user'], $_POST['pass'], $_POST['twitchId'], $_POST['twitchSecret'])
			|| !($host = trim($_POST['host'])) || !($name = trim($_POST['name']))
			|| !($user = trim($_POST['user'])) || !($pass = $_POST['pass'])
			|| !($twitchId = trim($_POST['twitchId'])) || !($twitchSecret = trim($_POST['twitchSecret']))
			|| !($googleId = trim($_POST['googleId'])) || !($googleSecret = trim($_POST['googleSecret']))
		)
			self::NeedMoreInfo('Parameters host, name, user, pass, twitchId, twitchSecret, googleId, and googleSecret are all required and cannot be blank.');
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
}
class KeysGoogle {
	const ClientId = \'' . addslashes($googleId) . '\';
	const ClientSecret = \'' . addslashes($googleSecret) . '\';
}';
		if ($fh = fopen($path, 'w')) {
			fwrite($fh, $contents);
			self::Success(array_merge(get_object_vars(self::currentLevel()), ['path' => $path, 'saved' => true]));
		} else
			self::Success(['path' => $path, 'saved' => false, 'message' => error_get_last()['message'], 'template' => self::GetKeysTemplate()]);
	}

	/**
	 * Install the database at the latest version.
	 */
	protected static function POST_installDatabase(): void {
		$db = self::RequireDatabase();
		// tables, views, routines; then alphabetical order.  if anything has
		// dependencies that come later, it comes after its last dependency.
		$files = [
			'table/config',
			'table/profile',
			'table/player',
			'table/account',
			'table/cookie',
			'table/email'
		];
		$db->begin_transaction();  // no partial database installations
		foreach ($files as $file)
			self::RunQueryFile($file, $db);

		try {
			$version = Version::Structure;
			$insert = $db->prepare('insert into config (structureVersion) values (?)');
			$insert->bind_param('i', $version);
			$insert->execute();
		} catch (mysqli_sql_exception $mse) {
			self::DatabaseError('Error initializing configuration', $mse);
		}
		$db->commit();
		self::Success();
	}

	/**
	 * Run all applicable upgrade scripts to bring the database up to the current version.
	 */
	protected static function POST_upgradeDatabase(): void {
		$db = self::RequireDatabaseWithConfig();
		if ($db->structureVersion < Version::Structure)
			self::UpgradeDatabaseStructure($db->database);
		self::Success();
	}

	/**
	 * Get the contents of the keys template file.
	 * @return string Contents of the keys template file
	 */
	private static function GetKeysTemplate(): string {
		return file_get_contents(Url::DocRoot() . '/etc/class/lanaKeys.template.php');
	}

	/**
	 * Upgrade database structure and update the structure version.
	 * @param mysqli $db Database connection object.
	 */
	private static function UpgradeDatabaseStructure(mysqli $db): void {
		// add future structure upgrades here (older ones need to go first)
	}

	/**
	 * Perform one step of a data structure upgrade.
	 * @param int $ver Structure version upgrading to (use a constant from StructureVersion)
	 * @param mysqli $db Database connection object
	 * @param string[] $queryfiles File subdirectory and name without extension for each query file to run
	 */
	private static function UpgradeDatabaseStructureStep(int $version, mysqli $db, string ...$queryfiles): void {
		if ($db->config->structureVersion < $version) {
			$db->begin_transaction();  // each step should commit only if the entire step succeeds
			foreach ($queryfiles as $file)
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
	private static function RunQueryFile(string $filepath, mysqli $db): void {
		$sql = trim(file_get_contents(dirname(__DIR__) . '/etc/db/' . $filepath . '.sql'));
		if (substr($filepath, 0, 12) == 'transition/') {  // transitions usually have more than one query
			if ($db->multi_query($sql)) {
				while ($db->next_result());  // these queries don't return results but we need to get past them to continue
				return;
			}
		} else {
			if ($db->real_query($sql))
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
	private static function SetStructureVersion(int $ver, mysqli $db): void {
		try {
			$update = $db->prepare('update config set structureVersion=? limit 1');
			$update->bind_param('i', $ver);
			$update->execute();
			$db->config->structureVersion = +$ver;
		} catch (mysqli_sql_exception $mse) {
			self::DatabaseError("Error setting structure version to $ver", $mse);
		}
	}
}
SetupApi::Respond();
