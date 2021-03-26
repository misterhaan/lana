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
	 * @return int Profile ID
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Add(mysqli $db, string $name, string $url, string $avatar) {
		if($add = $db->prepare('insert into profile (name, url, avatar) values (?, ?, ?)'))
			if($add->bind_param('sss', $name, $url, $avatar))
				if($add->execute())
					if($id = $add->insert_id) {
						$add->close();
						return $id;
					} else
						throw new DatabaseException('Unable to get ID of new profile', $add);
				else
					throw new DatabaseException('Error executing profile add query', $add);
			else
				throw new DatabaseException('Error binding profile add parameters', $add);
		else
			throw new DatabaseException('Error preparing to add profile', $db);
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
	public static function Update(mysqli $db, int $id, string $name, string $url, string $avatar) {
		if($update = $db->prepare('update profile set name=?, url=?, avatar=? where id=? limit 1'))
			if($update->bind_param('sssi', $name, $url, $avatar, $id))
				if($update->execute())
					$update->close();
				else
					throw new DatabaseException('Error executing profile update query', $update);
			else
				throw new DatabaseException('Error binding profile update parameters', $update);
		else
			throw new DatabaseException('Error preparing to update profile', $db);
	}

	/**
	 * Delete a profile.  Usually done when deleting an account or email.
	 * @param mysqli $db Database connection object
	 * @param int $id Profile ID to delete
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Delete(mysqli $db, int $id) {
		if($del = $db->prepare('delete from profile where id=? limit 1'))
			if($del->bind_param('i', $id))
				if($del->execute())
					$del->close();
				else
					throw new DatabaseException('Error executing profile delete query', $del);
			else
				throw new DatabaseException('Error binding profile delete parameter', $del);
		else
			throw new DatabaseException('Error preparing to delete profile', $db);
	}
}
