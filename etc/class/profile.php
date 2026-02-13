<?php

/**
 * Data and operations involving the profile table.  Profiles are information
 * to help identify accounts attached to players.
 */
class Profile {
	protected const SortType = ['email' => 1, 'google' => 2, 'steam' => 3, 'twitch' => 4, 'web' => 5];

	public string $type;
	public string $name;
	public string $url;

	protected function __construct(string $type, string $name, string $url) {
		$this->type = $type;
		$this->name = $name;
		$this->url = $url;
	}

	/**
	 * List a player’s profiles for showing to someone else.
	 * @param $db Database connection
	 * @param ?PlayerOne $player Signed-in player
	 * @param $profilePlayer ID of player whose profiles to list
	 * @return self[] Array of player’s profiles
	 */
	public static function List(mysqli $db, ?PlayerOne $player, int $profilePlayer): array {
		try {
			$playerId = $player?->id ?? 0;
			$select = $db->prepare('select type, name, url from profile_link where player=? and (visibility=3 or visibility=2 and ?!=0 or visibility=0 and ?=?)');
			$select->bind_param('iiii', $profilePlayer, $playerId, $profilePlayer, $playerId);
			$select->execute();
			/** @var string $name */
			/** @var string $url */
			$select->bind_result($type, $name, $url);
			$links = [];
			while ($select->fetch())
				$links[] = new self($type, $name, $url);
			$select->close();
			usort($links, function ($a, $b) {
				return self::SortType[$a->type] - self::SortType[$b->type];
			});
			return $links;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up player profiles', $mse);
		}
	}

	/**
	 * Add a profile.  Done after registering a new player or attaching another
	 * sign-in account.
	 * @param mysqli $db Database connection object
	 * @param string $name Name of profile
	 * @param string $url URL to profile
	 * @param ?string $avatar URL to avatar for profile
	 * @param Visibility $visibility Who can see this profile
	 * @return int Profile ID
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Add(mysqli $db, string $name, string $url, ?string $avatar, Visibility $visibility): int {
		$visibility = $visibility->value;
		try {
			$insert = $db->prepare('insert into profile (name, url, avatar, visibility) values (?, ?, ?, ?)');
			$insert->bind_param('sssi', $name, $url, $avatar, $visibility);
			$insert->execute();
			$id = $insert->insert_id;
			$insert->close();
			return $id;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error adding profile', $mse);
		}
	}

	/**
	 * Update a profile.  Usually done after signing in through external
	 * authentication site.
	 * @param mysqli $db Database connection object
	 * @param int $id Profile ID to update
	 * @param string $name Name of profile
	 * @param string $url URL to profile
	 * @param string $avatar URL to avatar for profile
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Update(mysqli $db, int $id, string $name, string $url, string $avatar): void {
		try {
			$update = $db->prepare('update profile set name=?, url=?, avatar=? where id=? limit 1');
			$update->bind_param('sssi', $name, $url, $avatar, $id);
			$update->execute();
			$update->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error updating profile', $mse);
		}
	}

	/**
	 * Delete a profile.  Usually done when deleting an account or email.
	 * @param mysqli $db Database connection object
	 * @param int $id Profile ID to delete
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Delete(mysqli $db, int $id): void {
		try {
			$delete = $db->prepare('delete from profile where id=? limit 1');
			$delete->bind_param('i', $id);
			$delete->execute();
			$delete->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error deleting profile', $mse);
		}
	}
}

/**
 * Data and operations for profiles that provide an avatar option
 */
class AvatarProfile {
	public int $id;
	public string $type;
	public string $url;
	public string $avatar;

	private function __construct(int $id, string $type, string $url, string $avatar) {
		$this->id = $id;
		$this->type = $type;
		$this->url = $url;
		$this->avatar = $avatar;
	}

	/**
	 * List a player’s profiles that have avatars.
	 * @param mysqli $db Database connection
	 * @param PlayerOne $player Signed-in player
	 * @return self[] Array of player’s profiles with avatars
	 */
	public static function List(mysqli $db, int $player): array {
		try {
			$select = $db->prepare('select p.id, pl.type, p.url, p.avatar from profile as p left join profile_link as pl on pl.id=p.id where pl.player=? and p.avatar is not null and p.avatar!=\'\' order by pl.type');
			$select->bind_param('i', $player);
			$select->execute();
			/** @var string $type */
			/** @var string $url */
			/** @var string $avatar */
			$select->bind_result($id, $type, $url, $avatar);
			$links = [];
			while ($select->fetch())
				$links[] = new self($id, $type, $url, $avatar);
			$select->close();
			return $links;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up avatar profiles', $mse);
		}
	}
}

/**
 * Data and operations involving the profile table that are only available to the signed-in user.
 */
class ProfileSettings extends Profile {
	public int $id;
	public Visibility $visibility;
	public bool $canDelete = false;

	private function __construct(int $id, string $type, string $name, string $url, int $visibility) {
		require_once CLASS_PATH . 'visibility.php';
		$this->id = $id;
		parent::__construct($type, $name, $url);
		$this->visibility = Visibility::from($visibility);
		$this->canDelete = $type == 'web';
	}

	/**
	 * Load profile links for the signed-in player’s settings.
	 * @param mysqli $db Database connection
	 * @param PlayerOne $player Signed-in player
	 */
	public static function Settings(mysqli $db, PlayerOne $player): array {
		try {
			$select = $db->prepare('select id, type, name, url, visibility from profile_link where player=? order by type');
			$select->bind_param('i', $player->id);
			$select->execute();
			/** @var string $type */
			/** @var string $name */
			/** @var string $url */
			/** @var int $visibility */
			$select->bind_result($id, $type, $name, $url, $visibility);
			$links = [];
			while ($select->fetch())
				$links[] = new self($id, $type, $name, $url, $visibility);
			return $links;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up link settings', $mse);
		}
	}

	/**
	 * Update visibility of a profile.  Can only update profiles owned by the player.
	 * @param mysqli $db Database connection object
	 * @param PlayerOne $player Signed-in player
	 * @param int $id Profile ID to update
	 * @param Visibility $visibility Visibility to set
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function UpdateVisibility(mysqli $db, PlayerOne $player, int $id, Visibility $visibility): void {
		try {
			$visibility = $visibility->value;
			$update = $db->prepare('update profile as p left join profile_link as pl on pl.id=p.id set p.visibility=? where p.id=? and (pl.player=?)');
			$update->bind_param('iii', $visibility, $id, $player->id);
			$update->execute();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error updating link visibility', $mse);
		}
	}

	/**
	 * Validate a profile URL.
	 * @param mysqli $db Database connection object
	 * @param PlayerOne $player Signed-in player
	 * @param string $url URL to validate
	 * @return string Reason the URL is invalid; empty string if valid
	 */
	public static function ValidateUrl(mysqli $db, PlayerOne $player, string $url): string {
		if (!filter_var($url, FILTER_VALIDATE_URL))
			return 'Invalid URL format';
		$parsed = parse_url($url);
		if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https']))
			return 'URL must use http or https';
		try {
			$select = $db->prepare('select name, type from profile_link where url=? and player=? limit 1');
			$select->bind_param('si', $url, $player->id);
			$select->execute();
			$select->bind_result($name, $type);
			if ($select->fetch())
				return "Your profile already has this URL as $name ($type)";
			$select->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error checking for duplicate link URL', $mse);
		}
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_exec($curl);
		$error = curl_error($curl);
		if ($error)
			return $error;
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($status >= 400)
			return "URL returned HTTP $status";
		return '';
	}

	/**
	 * Add a new link to the current player’s profile.
	 * @param mysqli $db Database connection object
	 * @param PlayerOne $player Signed-in player
	 * @param string $url URL to add
	 * @return self Newly added profile link
	 */
	public static function AddLink(mysqli $db, PlayerOne $player, string $url): self {
		$name = self::NameFromUrl($url);
		require_once CLASS_PATH . 'visibility.php';
		$visibility = Visibility::Players;
		try {
			$db->begin_transaction();

			$id = self::Add($db, $name, $url, null, $visibility);

			$insert = $db->prepare('insert into player_profile (player, profile) values (?, ?)');
			$insert->bind_param('ii', $player->id, $id);
			$insert->execute();
			$insert->close();

			$db->commit();
			return new self($id, 'web', $name, $url, $visibility->value, true);
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error adding profile link', $mse);
		}
	}

	/**
	 * Remove a link from the current player’s profile.
	 * @param mysqli $db Database connection object
	 * @param PlayerOne $player Signed-in player
	 * @param int $id Profile ID to remove, which must be owned by the player and not used for sign-in or email
	 */
	public static function RemoveLink(mysqli $db, PlayerOne $player, int $id): void {
		try {
			$db->begin_transaction();

			$delete = $db->prepare('delete from player_profile where player=? and profile=? limit 1');
			$delete->bind_param('ii', $player->id, $id);
			$delete->execute();
			if ($delete->affected_rows == 0) {
				$delete->close();
				throw new DatabaseException('Link not found in your profile or is used for sign-in or email');
			}
			$delete->close();

			Profile::Delete($db, $id);

			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error removing profile link', $mse);
		}
	}

	/**
	 * Get a profile name from its URL.
	 * @param string $url URL of profile
	 * @return string Name derived from URL
	 */
	private static function NameFromUrl(string $url): string {
		$parsed = parse_url($url);
		if (isset($parsed['path']) && $parsed['path'] != '/')
			return array_pop(explode('/', trim($parsed['path'], '/')));
		$name = $parsed['host'];
		if (str_starts_with($name, 'www.'))
			return substr($name, 4);
		return $name;
	}
}
