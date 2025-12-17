<?php
// PHP should treat strings as UTF8
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// active user is tracked in session
session_start();

/**
 * Base class for API controllers.  Requests are formed as
 * [controller]/[endpoint] with any required parameters separated by / after
 * the endpoint, and served by a function named [method]_[endpoint] in the Api
 * class in [controller].php.
 * @author misterhaan
 */
abstract class Api {
	/**
	 * Respond to an API request or show API documentation.
	 */
	public static function Respond(): void {
		if (!isset($_SERVER['PATH_INFO']) || substr($_SERVER['PATH_INFO'], 0, 1) != '/')
			self::NeedMoreInfo('API request must include an endpoint.');
		$method = $_SERVER['REQUEST_METHOD'];
		if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']))
			self::NotFound("Method $method is not supported.");
		$params = explode('/', substr($_SERVER['PATH_INFO'], 1));
		$method .= '_' . array_shift($params);  // turn the HTTP method and the endpoint into a php method name
		if (!method_exists(static::class, $method))
			self::NotFound('Requested endpoint does not exist on this controller or requires a different request method.');
		try {
			static::$method($params);
		} catch (mysqli_sql_exception $mse) {
			self::DatabaseError('API encountered an error', $mse);
		} catch (DatabaseException $de) {
			self::DatabaseError($de->getMessage(), $de->dbObject);
		}
	}

	/**
	 * Ensures the keys file exists and can be loaded.  Redirects to setup if not.
	 **/
	protected static function RequireKeys(): void {
		require_once 'url.php';
		if (!@include_once dirname(Url::DocRoot()) . '/.lanaKeys.php')
			self::NeedSetup('Connection details not defined.');
	}

	/**
	 * Ensures database connection information is available before continuing.
	 * Redirects to setup if connection information is not defined.
	 */
	protected static function RequireDatabaseKeys(): void {
		self::RequireKeys();
		if (!class_exists('KeysDB') || !defined('KeysDB::Host') || !defined('KeysDB::Name') || !defined('KeysDB::User') || !defined('KeysDB::Pass'))
			self::NeedSetup('Database connection details not specified or incomplete.');
	}

	/**
	 * Ensures authentication site keys are available before continuing.
	 * Redirects to setup if keys are not defined.
	 */
	protected static function RequireAuthSiteKeys(): void {
		self::RequireKeys();
		if (
			!class_exists('KeysTwitch') || !defined('KeysTwitch::ClientId')
			|| !class_exists('KeysGoogle') || !defined('KeysGoogle::ClientId')
		)
			self::NeedSetup('Twitch or Google keys not configured.');
	}

	/**
	 * Gets the database connection object.  Redirects to setup if unable to
	 * connect for any reason.  APIs other than setup should use
	 * RequireLatestDatabase instead.
	 * @return mysqli Database connection object.
	 */
	protected static function RequireDatabase(): mysqli {
		self::RequireDatabaseKeys();
		$db = @new mysqli(KeysDB::Host, KeysDB::User, KeysDB::Pass, KeysDB::Name);
		if ($db->connect_errno)
			self::NeedSetup('Error connecting to database.', $db);
		// it's probably okay to keep going if we can't set the character set
		$db->real_query('set names \'utf8mb4\'');
		$db->set_charset('utf8mb4');
		return $db;
	}

	/**
	 * Gets the database connection object along with the configuration record.
	 * Redirects to setup if anything is missing.  APIs other than setup should
	 * use RequireLatestDatabase instead.
	 * @return DatabaseWithConfiguration Database configuration and connection object.
	 */
	protected static function RequireDatabaseWithConfig(): DatabaseWithConfiguration {
		$db = self::RequireDatabase();
		try {
			$select = $db->prepare('select structureVersion from config limit 1');
			$select->execute();
			$select->bind_result($structureVersion);
			if (!$select->fetch())
				self::NeedSetup('Configuration not specified in database.');
			return new DatabaseWithConfiguration($db, $structureVersion);
		} catch (mysqli_sql_exception $mse) {
			self::NeedSetup('Error loading configuration from database', $mse);
			die;
		}
	}

	/**
	 * Gets the database connection object, making sure it's on the latest
	 * version.  Redirects to setup if anything is missing.  If this function
	 * returns at all, it's safe to use the database connection object.
	 * @return mysqli Database connection object.
	 */
	protected static function RequireLatestDatabase(): mysqli {
		$db = self::RequireDatabaseWithConfig();
		require_once 'version.php';
		if ($db->structureVersion < Version::Structure)
			self::NeedSetup('Database upgrade required.');
		return $db->database;
	}

	/**
	 * Look up the signed in player.
	 * @param mysqli $db Database connection object
	 * @param bool $canContinue True if the script should continue without a player
	 * @return ?PlayerOne Signed-in player, or null
	 */
	protected static function RequirePlayer(mysqli $db, $canContinue = false): ?PlayerOne {
		require_once CLASS_PATH . 'player.php';
		$player = PlayerOne::FromSession($db);
		if (!$player)
			$player = PlayerOne::FromCookie($db);
		if (!$canContinue && !$player)
			self::NeedSignin();
		return $player;
	}

	/**
	 * Read the request body as plain text.
	 */
	protected static function ReadRequestText(): string {
		$fp = fopen("php://input", "r");
		$text = '';
		while ('' !== $data = fread($fp, 1024))
			$text .= $data;
		return $text;
	}

	/**
	 * Send a successful response.
	 * @param mixed $data Response data (optional)
	 */
	protected static function Success($data = true): void {
		header('Content-Type: application/json');
		die(json_encode($data));
	}

	/**
	 * Reject the request because it is missing required information.
	 * @param string $message short message describing what's missing and how to provide it.
	 */
	protected static function NeedMoreInfo(string $message): void {
		http_response_code(422);
		header('Content-Type: text/plain');
		die($message);
	}

	/**
	 * Mark the request as encountering a database error.
	 * @param string $message failure reason
	 * @param mysqli_sql_exception|mysqli|mysqli_result $dbObject database object that threw this error (optional)
	 */
	protected static function DatabaseError(string $message, ?object $dbObject = null): void {
		http_response_code(500);
		header('Content-Type: text/plain');
		if ($dbObject)
			if ($dbObject instanceof mysqli_sql_exception)
				$message .= ':  ' . $dbObject->getCode() . ' ' . $dbObject->getMessage();
			elseif ($dbObject->errno)
				$message .= ":  $dbObject->errno $dbObject->error";
			else  // errno 0 means there's no error on $dbObject so show the last error instead
				$message .= ':  ' . error_get_last()['message'];
		die($message);
	}

	/**
	 * Mark the request as not found.  This probably only makes sense for get
	 * requests that look up an item by a key.
	 * @param string $message short message describing what was not found
	 */
	protected static function NotFound(string $message = ''): void {
		http_response_code(404);
		header('Content-Type: text/plain');
		die($message);
	}

	/**
	 * Return an error message and redirect to setup to perform any required
	 * updates.  Stops execution of the current script.
	 * @param string $message Error message to report.
	 * @param mysqli_sql_exception|mysqli|mysqli_result $dbObject database object that threw this error (optional)
	 */
	private static function NeedSetup(string $message, ?object $dbObject = null): void {
		$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
		header("$protocol 503 Setup Needed");
		header('Content-Type: text/plain');
		if ($dbObject)
			if ($dbObject instanceof mysqli_sql_exception)
				$message .= ":  $dbObject->getCode() $dbObject->getMessage()";
			else
				$message .= ":  $dbObject->errno $dbObject->error";
		die($message);
	}

	/**
	 * Mark the request as requiring authentication.  Used when we need to know
	 * who the player is but nobody is signed in.
	 */
	protected static function NeedSignin(): void {
		http_response_code(401);
		header('Content-Type: text/plain');
		header('WWW-Authenticate: OAuth realm="LAN Ahead player-specific areas"');
		die();
	}
}

class DatabaseWithConfiguration {
	public mysqli $database;
	public int $structureVersion;

	public function __construct(mysqli $database, int $structureVersion) {
		$this->database = $database;
		$this->structureVersion = $structureVersion;
	}
}

/**
 * Exception thrown when an error is encountered working with the database.
 */
class DatabaseException extends Exception {
	/**
	 * The database object in use when this exception happened (mysqli or mysqli_result)
	 */
	public $dbObject;

	/**
	 * Create a new exception with a database object for more error details.
	 * @param string $message The Exception message to throw
	 * @param mysqli_sql_exception|mysqli|mysqli_result $dbObject Database object that threw this error (optional)
	 */
	public function __construct(string $message, ?object $dbObject = null, int $code = 0, ?Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->dbObject = $dbObject;
	}
}
