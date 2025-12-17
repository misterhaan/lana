<?php

/**
 * Data and operations involving the profile table.  Profiles are information
 * to help identify accounts attached to players.
 */
class Profile {
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
