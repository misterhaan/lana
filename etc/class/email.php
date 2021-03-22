<?php
/**
 * Players link their email address(es) so other players can find them, and set
 * one of those as the address LANA should use to contact them.
 */
class Email {
	/**
	 * Add an email address linked to a player.
	 * @param mysqli $db Database connection object
	 * @param string $address Email address to add
	 * @param int $player ID of player the email address should be linked to
	 * @param int $profile ID of profile for the email address
	 * @param bool $makePrimary True if the email should be made the primary email for the player
	 * @throws DatabaseException Thrown when the database is unable to complete a request
	 */
	public static function Add(mysqli $db, string $address, int $player, int $profile, bool $makePrimary) {
		$makePrimary = +$makePrimary;
		if($makePrimary)
			self::ClearPrimary($db, $player);
		if($add = $db->prepare('insert into email (address, player, profile, isPrimary) values (?, ?, ?, ?)'))
			if($add->bind_param('siii', $address, $player, $profile, $makePrimary))
				if($add->execute())
					$add->close();
				else
					throw new DatabaseException('Error adding email', $add);
			else
				throw new DatabaseException('Error binding parameters to add email', $add);
		else
			throw new DatabaseException('Error preparing to add email', $db);
	}

	/**
	 * Check an email address for validity.  Does NOT check if the address is in
	 * use by a LANA player.
	 * @param string $address Potential email address to check
	 * @return bool True if address looks like an email and isn't example.com
	 */
	public static function Valid(string $address) {
		$address = explode('@', $address);
		if(count($address) != 2)
			return false;
		$domain = explode('.', $address[1]);
		if(count($domain) < 2)
			return false;
		return strtolower(substr($address[1], -11)) != 'example.com';
	}

	/**
	 * Look up the player ID an email is associated with.
	 * @param mysqli $db Database connection object
	 * @param string $address Email address to look up
	 * @return int|bool Player ID linked to the email, or false if none
	 * @throws DatabaseException Thrown when the database is unable to complete a request
	 */
	public static function UsedBy(mysqli $db, string $address) {
		if($get = $db->prepare('select player from email where address=? limit 1'))
			if($get->bind_param('s', $address))
				if($get->execute())
					if($get->bind_result($player))
						if($get->fetch()) {
							$get->close();
							return $player;
						} else {
							$get->close();
							return false;
						}
					else
						throw new DatabaseException('Error binding result of email address lookup', $get);
				else
					throw new DatabaseException('Error executing email address lookup', $get);
			else
				throw new DatabaseException('Error binding email address to look up', $get);
		else
			throw new DatabaseException('Error preparing to look up email address', $db);
	}

	/**
	 * Clear the primary flag from all emails for the specified player.  This
	 * should be followed by adding a new email for that player with the primary
	 * flag set, or setting the primary flag on an existing email.
	 * @param mysqli $db Database connection object
	 * @param int $player Player ID whose emails should be affected
	 * @throws DatabaseException Thrown when the database isn't able to complete a request
	 */
	private static function ClearPrimary(mysqli $db, int $player) {
		if($set = $db->prepare('update email set isPrimary=0 where player=?'))
			if($set->bind_param('i', $player))
				if($set->execute())
					$set->close();
				else
					throw new DatabaseException('Error clearing primary flag from other email addresses', $set);
			else
				throw new DatabaseException('Error binding player ID to update email addresses', $set);
		else
			throw new DatabaseException('Error preparing to update email addresses', $db);
	}
}
