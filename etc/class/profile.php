<?php

/**
 * Data and operations involving the profile table.  Profiles are information
 * to help identify accounts attached to players.
 */
class Profile {
	protected const SortType = ['email' => 1, 'google' => 2, 'steam' => 3, 'twitch' => 4];

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
			$select = $db->prepare('select coalesce(a.site, \'email\'), l.name, l.url from profile as l left join account as a on a.profile=l.id left join email as e on e.profile=l.id where coalesce(a.player, e.player)=? and (l.visibility=3 or l.visibility=2 and ?!=0 or l.visibility=0 and ?=?)');
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
	 * @param string $avatar URL to avatar for profile
	 * @param Visibility $visibility Who can see this profile
	 * @return int Profile ID
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Add(mysqli $db, string $name, string $url, string $avatar, Visibility $visibility): int {
		try {
			$insert = $db->prepare('insert into profile (name, url, avatar) values (?, ?, ?)');
			$insert->bind_param('sss', $name, $url, $avatar);
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
 * Data and operations involving the profile table that are only available to the signed-in user.
 */
class ProfileSettings extends Profile {
	public int $id;
	public Visibility $visibility;

	private function __construct(int $id, string $type, string $name, string $url, int $visibility) {
		require_once CLASS_PATH . 'visibility.php';
		$this->id = $id;
		parent::__construct($type, $name, $url);
		$this->visibility = Visibility::from($visibility);
	}

	/**
	 * Load profile links for the signed-in player’s settings.
	 * @param mysqli $db Database connection
	 * @param PlayerOne $player Signed-in player
	 */
	public static function Settings(mysqli $db, PlayerOne $player): array {
		$links = [];
		try {
			$select = $db->prepare('select p.id, p.name, p.url, p.visibility from email as e left join profile as p on p.id=e.profile where e.player=?');
			$select->bind_param('i', $player->id);
			$select->execute();
			/** @var string $name */
			/** @var string $url */
			/** @var int $visibility */
			$select->bind_result($id, $name, $url, $visibility);
			while ($select->fetch())
				$links[] = new self($id, 'email', $name, $url, $visibility);
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up email profile settings', $mse);
		}
		try {
			$select = $db->prepare('select p.id, a.site, p.name, p.url, p.visibility from account as a left join profile as p on p.id=a.profile where a.player=?');
			$select->bind_param('i', $player->id);
			$select->execute();
			/** @var string $type */
			/** @var string $name */
			/** @var string $url */
			/** @var int $visibility */
			$select->bind_result($id, $type, $name, $url, $visibility);
			while ($select->fetch())
				$links[] = new self($id, $type, $name, $url, $visibility);
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up account profile settings', $mse);
		}
		usort($links, function ($a, $b) {
			return self::SortType[$a->type] - self::SortType[$b->type];
		});
		return $links;
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
			$update = $db->prepare('update profile as p left join email as e on e.profile=p.id left join account as a on a.profile=p.id set p.visibility=? where p.id=? and (e.player=? or a.player=?)');
			$update->bind_param('iiii', $visibility, $id, $player->id, $player->id);
			$update->execute();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error updating link visibility', $mse);
		}
	}
}
