<?php

/**
 * Data for players as shown in the list of players.  A player
 * represents a user of the LANA website.
 */
class Player {
	/**
	 * Player's username, which can be shown to anyone.
	 */
	public string $username = 'Player 2';

	/**
	 * URL to player's avatar.
	 */
	public string $avatar = '';

	protected function __construct(string $username, string $avatar) {
		$this->username = $username;
		$this->avatar = $avatar;
	}

	/**
	 * List all registered players.
	 * @param mysqli $db Database connection
	 * @return self[] Registered players
	 */
	public static function List(mysqli $db): array {
		try {
			// TODO: support sorting, filtering and incremental loading
			$select = $db->prepare('select p.username, a.avatar from player as p left join profile as a on a.id=p.avatarProfile');
			$select->execute();
			/** @var string $avatar */
			$select->bind_result($username, $avatar);
			$players = [];
			while ($select->fetch())
				$players[] = new self($username, $avatar);
			return $players;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up player list', $mse);
		}
	}
}

/**
 * Data and operations for a player with profile information.
 */
class PlayerProfile extends Player {
	/**
	 * How long the player has been registered here
	 */
	public TimeTagData $joined;

	/**
	 * External profile links
	 */
	public array $links = [];

	private function __construct(string $username, int $joined, string $avatar) {
		require_once 'datetime.php';
		$this->joined = new TimeTagData('ago', $joined, FormatDate::Long);
		parent::__construct($username, $avatar);
	}

	/**
	 * Look up a player based on their username.
	 * @param mysqli $db Database connection
	 * @param ?PlayerOne $player Signed-in player
	 * @param string $name Username to look up
	 * @return ?self Player information, or null if not found.
	 */
	public static function FromName(mysqli $db, ?PlayerOne $player, string $name): ?self {
		try {
			$select = $db->prepare('select p.id, p.username, unix_timestamp(p.firstLogin), pr.avatar from player as p left join profile as pr on pr.id=p.avatarProfile where p.username=? limit 1');
			$select->bind_param('s', $name);
			$select->execute();
			/** @var string $username */
			/** @var int $joined */
			/** @var string $avatar */
			$select->bind_result($id, $username, $joined, $avatar);
			if (!$select->fetch())
				return null;
			$select->close();
			$profilePlayer = new self($username, $joined, $avatar);
			require_once 'profile.php';
			$profilePlayer->links = Profile::List($db, $player, $id);
			return $profilePlayer;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up player ' . $name, $mse);
		}
	}
}

/**
 * Data and operations for the player currently signed into the website.
 */
class PlayerOne extends Player {
	private const Session = 'player';

	/**
	 * Record ID of this player, or zero if player not signed in.
	 */
	public int $id = 0;

	/**
	 * Player's real name, which can be shown to some friends.
	 */
	public string $realName = '';

	/**
	 * Create a player object looked up from the database.
	 * @param int $id LANA player ID
	 * @param mysqli $db Database connection object
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public function __construct(int $id, mysqli $db) {
		try {
			$select = $db->prepare('select p.id, p.username, p.realName, pr.avatar from player as p left join profile as pr on pr.id=p.avatarProfile where p.id=? limit 1');
			$select->bind_param('i', $id);
			$select->execute();
			$select->bind_result($this->id, $this->username, $this->realName, $this->avatar);
			if ($select->fetch())
				$select->close();
			else
				throw new DatabaseException('Player not found');
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up player', $mse);
		}
	}

	/**
	 * Look up a player from the current session.
	 * @param mysqli $db Database connection object
	 * @return ?self The LANA player record in the session or null if none
	 */
	public static function FromSession(mysqli $db): ?self {
		if (isset($_SESSION[self::Session]) && $id = +$_SESSION[self::Session]) {
			$player = new self($id, $db);
			$player->UpdateLastRequest($db);
			return $player;
		}
		return null;
	}

	/**
	 * Look up a player from an autosignin cookie.
	 * @param mysqli $db Database connection object
	 * @return ?self The LANA player record from a verified autologin cookie or null if none
	 */
	public static function FromCookie(mysqli $db): ?self {
		require_once 'cookie.php';
		if ($id = Cookie::Verify($db)) {
			$player = new self($id, $db);
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
	 * @return ?self Either the LANA player record associated with the external account, or a LANA player record with ID 0 if no associated player record
	 */
	public static function FromAuth(mysqli $db, string $site, string $account, string $name, string $url, string $avatar): ?self {
		require_once 'account.php';
		$account = new Account($site, $account, $db);
		if ($account->player) {
			if ($account->profile) {
				require_once 'profile.php';
				Profile::Update($db, +$account->profile, $name, $url, $avatar);
			}
			$player = new self(+$account->player, $db);
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
	 * @return self Newly-created Player record
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function FromRegister(mysqli $db, string $username, string $realName = ''): self {
		try {
			$insert = $db->prepare('insert into player (username, realName) values (?, ?)');
			$insert->bind_param('ss', $username, $realName);
			$insert->execute();
			$id = $insert->insert_id;
			$insert->close();
			$_SESSION[self::Session] = $id;
			return new self($id, $db);
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error registering player', $mse);
		}
	}

	/**
	 * Remove the authentication information to sign out the current player.
	 * @param mysqli $db Database connection object
	 */
	public static function Signout(mysqli $db): void {
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
	public static function ValidUsername(string $username): bool {
		$len = strlen($username);
		return $len >= 4 && $len <= 20;
	}

	/**
	 * Look up the player ID from a username.
	 * @param mysqli $db Database connection object
	 * @param string $username Username to look up
	 * @return ?int Player ID for the given username, or null if username not in use
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function UsernameUsedBy(mysqli $db, string $username): ?int {
		try {
			$select = $db->prepare('select id from player where username=? limit 1');
			$select->bind_param('s', $username);
			$select->execute();
			$select->bind_result($id);
			if ($select->fetch())
				return $id;
			else
				return null;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up username', $mse);
		}
	}

	/**
	 * Add an external account linked to this player.
	 * @param mysqli $db Dataabase connection object
	 * @param string $site ID of external authentication site
	 * @param AuthenticationAccount $account External account details
	 * @param bool $useProfileForAvatar True if the profile created here should be used for the player's avatar
	 */
	public function AddAccount(mysqli $db, string $site, AuthenticationAccount $account, bool $useProfileForAvatar = false): void {
		$db->begin_transaction();
		require_once 'profile.php';
		require_once 'visibility.php';
		$visibility = str_starts_with($account->profileUrl, 'mailto:') ? Visibility::Self : Visibility::Players;
		$profile = Profile::Add($db, $account->username, $account->profileUrl, $account->avatarUrl, $visibility);
		require_once 'account.php';
		Account::Save($db, $site, $account->accountId, $this->id, $profile);
		if ($useProfileForAvatar)
			$this->SetAvatarProfile($db, $profile);
		$db->commit();
	}

	/**
	 * Add an email address linked to this player.
	 * @param mysqli $db Dataabase connection object
	 * @param string $email Email address to add
	 * @param bool $makePrimary True if this email address should be used for emails from LANA
	 * @param bool $useGravatarForAvatar True if the profile created here should be used for the player's avatar
	 */
	public function AddEmail(mysqli $db, string $email, bool $makePrimary = false, bool $useGravatarForAvatar = false): void {
		$db->begin_transaction();
		require_once 'profile.php';
		require_once 'visibility.php';
		$profile = Profile::Add($db, $email, 'mailto:' . $email, 'https://www.gravatar.com/avatar/' . md5(strtolower($email)) . '?s=128&amp;d=monsterid', Visibility::Self);
		require_once 'email.php';
		Email::Add($db, $email, $this->id, $profile, $makePrimary);
		if ($useGravatarForAvatar)
			$this->SetAvatarProfile($db, $profile);
		$db->commit();
	}

	/**
	 * Use a profile as the player's avatar.  The profile should have an avatar defined.
	 * @param mysqli $db Dataabase connection object
	 * @param int $profileId ID of profile to use as player avatar
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public function SetAvatarProfile(mysqli $db, int $profileId): void {
		try {
			$update = $db->prepare('update player set avatarProfile=? where id=? limit 1');
			$update->bind_param('ii', $profileId, $this->id);
			$update->execute();
			$update->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error updating avatar profile', $mse);
		}
	}

	/**
	 * Get all accounts linked to the player.
	 * @param mysqli $db Database connection object
	 * @return AccountWithProfile[] Accounts linked to the player
	 */
	public function GetAccounts(mysqli $db): array {
		require_once 'account.php';
		return Account::ListForPlayer($db, $this->id);
	}

	/**
	 * Update the last login (and last request) instant for this player to now.
	 * @param mysqli $db Database connection object
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	private function UpdateLastLogin(mysqli $db): void {
		try {
			$update = $db->prepare('update player set lastLogin=now(), lastRequest=now() where id=? limit 1');
			$update->bind_param('i', $this->id);
			$update->execute();
			$update->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error updating last login instant', $mse);
		}
	}

	/**
	 * Update the last request instant for this player to now.
	 * @param mysqli $db Database connection object
	 */
	private function UpdateLastRequest(mysqli $db) {
		try {
			$update = $db->prepare('update player set lastRequest=now() where id=? limit 1');
			$update->bind_param('i', $this->id);
			$update->execute();
			$update->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error updating last request instant', $mse);
		}
	}
}
