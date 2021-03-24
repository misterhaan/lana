<?php
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
			if($get = $db->prepare("select player, profile from account where site=? and id=? limit 1"))
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
			if($add = $db->prepare("insert into account (site, id, player, profile) values (?, ?, ?, ?)"))
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
	 * Verify that a site ID is configured.
	 */
	private static function VerifySite(string $site) {
		require_once 'auth.php';
		return AuthController::FindSite($site);
	}
}
