<?php
if (!defined('CLASS_PATH'))
	define('CLASS_PATH', __DIR__ . '/');

/**
 * External sign-in accounts linked to LANA players.
 */
class Account {
	/**
	 * ID of the player this account is linked to.
	 */
	public ?int $player = null;

	/**
	 * ID of the profile record for this account.
	 */
	public ?int $profile = null;

	/**
	 * Look up an external sign-in account.  Properties will all be false if
	 * account is not found.
	 * @param string $site ID of external authentication site
	 * @param string $id Account identifier
	 * @param mysqli $db Database connection object
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 * @throws Error Thrown when $site does not match a defined external authentication site
	 */
	public function __construct(string $site, string $id, mysqli $db) {
		self::VerifySite($site);
		try {
			$select = $db->prepare('select player, profile from account where site=? and id=? limit 1');
			$select->bind_param('ss', $site, $id);
			$select->execute();
			$select->bind_result($this->player, $this->profile);
			$select->fetch();
			$select->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up account', $mse);
		}
	}

	/**
	 * Save an external sign-in account.
	 * @param mysqli $db Database connection object
	 * @param string $site ID of external authentication site
	 * @param string $id Account identifier
	 * @param int $player ID of the player this account is linked to
	 * @param int $profile ID of the profile record for this account
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 * @throws Error Thrown when $site does not match a defined external authentication site
	 */
	public static function Save(mysqli $db, string $site, string $id, int $player, int $profile): void {
		self::VerifySite($site);
		try {
			$insert = $db->prepare('insert into account (site, id, player, profile) values (?, ?, ?, ?)');
			$insert->bind_param('ssii', $site, $id, $player, $profile);
			$insert->execute();
			$insert->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error adding account', $mse);
		}
	}

	/**
	 * Delete an external sign-in account.
	 * @param mysqli $db Database connection object
	 * @param string $site ID of external authentication site
	 * @param string $id Account identifier
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Delete(mysqli $db, string $site, string $id): void {
		try {
			$delete = $db->prepare('delete from account where site=? and id=? limit 1');
			$delete->bind_param('ss', $site, $id);
			$delete->execute();
			$delete->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error deleting account', $mse);
		}
	}

	/**
	 * Get all accounts linked to a player.
	 * @param mysqli $db Database connection object
	 * @param int $player ID of player to look up
	 * @return AccountWithProfile[] Accounts linked to the player
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function ListForPlayer(mysqli $db, int $player): array {
		try {
			$select = $db->prepare('select a.site, a.id, p.name, p.url, p.avatar from account as a left join profile as p on p.id=a.profile where a.player=?');
			$select->bind_param('i', $player);
			$select->execute();
			$select->bind_result($site, $id, $name, $url, $avatar);
			$accounts = [];
			while ($select->fetch())
				$accounts[] = new AccountWithProfile($site, $id, $name, $url, $avatar);
			$select->close();
			return $accounts;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up player accounts', $mse);
		}
	}

	/**
	 * Verify that a site ID is configured.
	 */
	private static function VerifySite(string $site): object {
		require_once CLASS_PATH . 'auth.php';
		$auth = AuthController::FindSite($site);
		if ($auth)
			return $auth;
		else
			throw new Error("Site ID $site not defined.");
	}
}

class AccountWithProfile {
	public string $site;
	public string $id;
	public string $name;
	public string $url;
	public string $avatar;

	public function __construct(string $site, string $id, string $name, string $url, string $avatar) {
		$this->site = $site;
		$this->id = $id;
		$this->name = $name;
		$this->url = $url;
		$this->avatar = $avatar;
	}
}
