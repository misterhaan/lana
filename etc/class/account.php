<?php
if(!defined('CLASS_PATH'))
	define('CLASS_PATH', __DIR__ . '/');

	/**
 * External sign-in accounts linked to LANA players.
 */
class Account {
	/**
	 * ID of the player this account is linked to.
	 */
	public $player = false;

	/**
	 * ID of the profile record for this account.
	 */
	public $profile = false;

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
		if(self::VerifySite($site))
			if($get = $db->prepare('select player, profile from account where site=? and id=? limit 1'))
				if($get->bind_param('ss', $site, $id))
					if($get->execute())
						if($get->bind_result($this->player, $this->profile)) {
							if($get->fetch()) {
								$this->player = +$this->player;
								$this->profile = +$this->profile;
							}
							$get->close();
						} else
							throw new DatabaseException('Error binding account lookup result', $get);
					else
						throw new DatabaseException('Error executing account lookup query', $get);
				else
					throw new DatabaseException('Error binding account ID for lookup', $get);
			else
				throw new DatabaseException('Error preparing to look up account', $db);
		else
			throw new Error("Site ID $site not defined.");
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
	public static function Save(mysqli $db, string $site, string $id, int $player, int $profile) {
		if(self::VerifySite($site))
			if($add = $db->prepare('insert into account (site, id, player, profile) values (?, ?, ?, ?)'))
				if($add->bind_param('ssii', $site, $id, $player, $profile))
					if($add->execute())
						$add->close();
					else
						throw new DatabaseException('Error adding account', $add);
				else
					throw new DatabaseException('Error binding parameters to add account', $add);
			else
				throw new DatabaseException('Error preparing to add account', $db);
		else
			throw new Error("Site ID $site not defined.");
	}

	/**
	 * Delete an external sign-in account.
	 * @param mysqli $db Database connection object
	 * @param string $site ID of external authentication site
	 * @param string $id Account identifier
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Delete(mysqli $db, string $site, string $id) {
		if($del = $db->prepare('delete from account where site=? and id=? limit 1'))
			if($del->bind_param('ss', $site, $id))
				if($del->execute())
					$del->close();
				else
					throw new DatabaseException('Error deleting account', $del);
			else
				throw new DatabaseException('Error binding parameters to delete account', $del);
		else
			throw new DatabaseException('Error preparing to delete account', $db);
	}

	/**
	 * Get all accounts linked to a player.
	 * @param mysqli $db Database connection object
	 * @param int $player ID of player to look up
	 * @return AccountWithProfile[] Accounts linked to the player
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function ListForPlayer(mysqli $db, int $player) {
		if($get = $db->prepare('select a.site, a.id, p.name, p.url, p.avatar from account as a left join profile as p on p.id=a.profile where a.player=?'))
			if($get->bind_param('i', $player))
				if($get->execute())
					if($get->bind_result($site, $id, $name, $url, $avatar)) {
						$accounts = [];
						while($get->fetch())
							$accounts[] = new AccountWithProfile($site, $id, $name, $url, $avatar);
						$get->close();
						return $accounts;
					} else
						throw new DatabaseException('Error binding result from player account lookup', $get);
				else
					throw new DatabaseException('Error looking up player accounts', $get);
			else
				throw new DatabaseException('Error binding parameter to look up player accounts', $get);
		else
			throw new DatabaseException('Error preparing to look up player accounts', $db);
	}

	/**
	 * Verify that a site ID is configured.
	 */
	private static function VerifySite(string $site) {
		require_once CLASS_PATH . 'auth.php';
		return AuthController::FindSite($site);
	}
}

class AccountWithProfile {
	public $site;
	public $id;
	public $name;
	public $url;
	public $avatar;

	public function __construct(string $site, string $id, string $name, string $url, string $avatar) {
		$this->site = $site;
		$this->id = $id;
		$this->name = $name;
		$this->url = $url;
		$this->avatar = $avatar;
	}
}
