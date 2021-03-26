<?php
/**
 * Data and operations involving the player table.  A player
 * represents a user of the LANA website.
 */
class Player {
	private const Session = 'player';

	/**
	 * Record ID of this player, or zero if player not signed in.
	 */
	public $id = 0;

	/**
	 * Player's username, which can be shown to anyone.
	 */
	public $username = 'Player 2';

	/**
	 * Player's real name, which can be shown to some friends.
	 */
	public $realName = '';

	/**
	 * URL to player's avatar.
	 */
	public $avatar = '';

	/**
	 * Create a player object looked up from the database.
	 * @param int $id LANA player ID
	 * @param mysqli $db Database connection object
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public function __construct(int $id, mysqli $db) {
		if($get = $db->prepare('select p.id, p.username, p.realName, pr.avatar from player as p left join profile as pr on pr.id=p.avatarProfile where p.id=? limit 1'))
			if($get->bind_param('i', $id))
				if($get->execute())
					if($get->bind_result($this->id, $this->username, $this->realName, $this->avatar))
						if($get->fetch()) {
							$get->close();
							$this->id = +$this->id;
						} else
							throw new DatabaseException('Player not found');
					else
						throw new DatabaseException('Error binding player lookup result', $get);
				else
					throw new DatabaseException('Error executing player lookup query', $get);
			else
				throw new DatabaseException('Error binding player ID for lookup', $get);
		else
			throw new DatabaseException('Error preparing to look up player', $db);
	}

	/**
	 * Look up a player from the current session.
	 * @param mysqli $db Database connection object
	 * @return Player The LANA player record in the session or null if none
	 */
	public static function FromSession(mysqli $db) {
		if(isset($_SESSION[self::Session]) && $id = +$_SESSION[self::Session]) {
			$player = new Player($id, $db);
			$player->UpdateLastRequest($db);
			return $player;
		}
		return null;
	}

	/**
	 * Look up a player from an autosignin cookie.
	 * @param mysqli $db Database connection object
	 * @return Player The LANA player record from a verified autologin cookie or null if none
	 */
	public static function FromCookie(mysqli $db) {
		require_once 'cookie.php';
		if($id = Cookie::Verify($db)) {
			$player = new Player($id, $db);
			$player->UpdateLastLogin($db);
			return $player;
		}
		return null;
	}

	/**
	 * Look up a player from an external account that has been authenticated.
	 * @param mysqli $db Database connection object
	 * @param string $site ID of external authentication site
	 * @param string $account ID of account on external authentication site
	 * @param string $name Name of profile on external site
	 * @param string $url URL to profile on external site
	 * @param string $avatar URL to avatar of profile on external site
	 * @return Player Either the LANA player record associated with the external account, or a LANA player record with ID 0 if no associated player record
	 */
	public static function FromAuth(mysqli $db, string $site, string $account, string $name, string $url, string $avatar) {
		require_once 'account.php';
		$account = new Account($site, $account, $db);
		if(+$account->player) {
			if(+$account->profile) {
				require_once 'profile.php';
				Profile::Update($db, +$account->profile, $name, $url, $avatar);
			}
			$player = new Player(+$account->player, $db);
			$_SESSION[self::Session] = $player->id;
			$player->UpdateLastLogin($db);
			return $player;
		}
		return null;
	}

	/**
	 * Create a new player from registration information.
	 * @param mysqli $db Database connection object
	 * @param string $username Player username
	 * @param string $realName Player real name
	 * @return Player Newly-created Player record
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function FromRegister(mysqli $db, string $username, string $realName = '') {
		if($put = $db->prepare('insert into player (username, realName) values (?, ?)'))
			if($put->bind_param('ss', $username, $realName))
				if($put->execute())
					if($id = $put->insert_id) {
						$put->close();
						$_SESSION[self::Session] = $id;
						return new Player($id, $db);
					} else
						throw new DatabaseException('Unable to obtain player ID', $put);
				else
					throw new DatabaseException('Error registering player', $put);
			else
				throw new DatabaseException('Error binding parameters to register player', $put);
		else
			throw new DatabaseException('Error preparing to register player', $db);
	}

	/**
	 * Remove the authentication information to sign out the current player.
	 * @param mysqli $db Database connection object
	 */
	public static function Signout(mysqli $db) {
		unset($_SESSION[self::Session]);
		require_once 'cookie.php';
		Cookie::Forget($db);
	}

	/**
	 * Check a username for validity.  Does NOT check if the username is already
	 * in use by another player.
	 * @param string $username Username to check
	 * @return bool True if the username is valid
	 */
	public static function ValidUsername(string $username) {
		$len = strlen($username);
		return $len >= 4 && $len <= 20;
	}

	/**
	 * Look up the player ID from a username.
	 * @param mysqli $db Database connection object
	 * @param string $username Username to look up
	 * @return int Player ID for the given username, or falsy if username not in use
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function UsernameUsedBy(mysqli $db, string $username) {
		if($get = $db->prepare('select id from player where username=? limit 1'))
			if($get->bind_param('s', $username))
				if($get->execute())
					if($get->bind_result($id))
						if($get->fetch())
							return +$id;
						else
							return false;
					else
						throw new DatabaseException('Error binding result from looking up username', $get);
				else
					throw new DatabaseException('Error looking up username', $get);
			else
				throw new DatabaseException('Error binding parameter to look up username', $get);
		else
			throw new DatabaseException('Error preparing to look up username', $db);
	}

	/**
	 * Add an external account linked to this player.
	 * @param mysqli $db Dataabase connection object
	 * @param string $site ID of external authentication site
	 * @param AuthenticationAccount $account External account details
	 * @param bool $useProfileForAvatar True if the profile created here should be used for the player's avatar
	 */
	public function AddAccount(mysqli $db, string $site, AuthenticationAccount $account, bool $useProfileForAvatar = false) {
		require_once 'profile.php';
		$profile = Profile::Add($db, $account->username, $account->profileUrl, $account->avatarUrl);
		require_once 'account.php';
		Account::Save($db, $site, $account->accountId, $this->id, $profile);
		if($useProfileForAvatar)
			$this->SetAvatarProfile($db, $profile);
	}

	/**
	 * Add an email address linked to this player.
	 * @param mysqli $db Dataabase connection object
	 * @param string $email Email address to add
	 * @param bool $makePrimary True if this email address should be used for emails from LANA
	 * @param bool $useGravatarForAvatar True if the profile created here should be used for the player's avatar
	 */
	public function AddEmail(mysqli $db, string $email, bool $makePrimary = false, bool $useGravatarForAvatar = false) {
		require_once 'profile.php';
		$profile = Profile::Add($db, $email, 'mailto:' . $email, 'https://www.gravatar.com/avatar/' . md5(strtolower($email)) . '?s=128&amp;d=monsterid');
		require_once 'email.php';
		Email::Add($db, $email, $this->id, $profile, $makePrimary);
		if($useGravatarForAvatar)
			$this->SetAvatarProfile($db, $profile);
	}

	/**
	 * Use a profile as the player's avatar.  The profile should have an avatar defined.
	 * @param mysqli $db Dataabase connection object
	 * @param int $profileId ID of profile to use as player avatar
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public function SetAvatarProfile(mysqli $db, int $profileId) {
		if($set = $db->prepare('update player set avatarProfile=? where id=? limit 1'))
			if($set->bind_param('ii', $profileId, $this->id))
				if($set->execute())
					$set->close();
				else
					throw new DatabaseException('Error updating avatar profile', $set);
			else
				throw new DatabaseException('Error binding parameters to update avatar profile', $set);
		else
			throw new DatabaseException('Error preparing to update avatar profile', $db);
	}

	/**
	 * Get all accounts linked to the player.
	 * @param mysqli $db Database connection object
	 * @return AccountWithProfile[] Accounts linked to the player
	 */
	public function GetAccounts(mysqli $db) {
		require_once 'account.php';
		return Account::ListForPlayer($db, $this->id);
	}

	/**
	 * Update the last login (and last request) instant for this player to now.
	 * @param mysqli $db Database connection object
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	private function UpdateLastLogin(mysqli $db) {
		if($update = $db->prepare('update player set lastLogin=now(), lastRequest=now() where id=? limit 1'))
			if($update->bind_param('i', $this->Id))
				if($update->execute())
					$update->close();
				else
					throw new DatabaseException('Error executing last login update query', $update);
			else
				throw new DatabaseException('Error binding player ID for last login update', $update);
		else
			throw new DatabaseException('Error preparing to update last login instant', $db);
	}

	/**
	 * Update the last request instant for this player to now.
	 * @param mysqli $db Database connection object
	 */
	private function UpdateLastRequest(mysqli $db) {
		if($update = $db->prepare('update player set lastRequest=now() where id=? limit 1'))
			if($update->bind_param('i', $this->Id))
				if($update->execute())
					$update->close();
				else
					throw new DatabaseException('Error executing last login update query', $update);
			else
				throw new DatabaseException('Error binding player ID for last login update', $update);
		else
			throw new DatabaseException('Error preparing to update last login instant', $db);
	}
}
