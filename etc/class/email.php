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
	public static function Add(mysqli $db, string $address, int $player, int $profile, bool $makePrimary): void {
		if ($makePrimary)
			self::ClearPrimary($db, $player);
		try {
			$insert = $db->prepare('insert into email (address, player, profile, isPrimary) values (?, ?, ?, ?)');
			$insert->bind_param('siii', $address, $player, $profile, $makePrimary);
			$insert->execute();
			$insert->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error adding email', $mse);
		}
	}

	/**
	 * Check an email address for validity.  Does NOT check if the address is in
	 * use by a LANA player.
	 * @param string $address Potential email address to check
	 * @return bool True if address looks like an email and isn't example.com
	 */
	public static function Valid(string $address): bool {
		$address = explode('@', $address);
		if (count($address) != 2)
			return false;
		$domain = explode('.', $address[1]);
		if (count($domain) < 2)
			return false;
		return strtolower(substr($address[1], -11)) != 'example.com';
	}

	/**
	 * Look up the player ID an email is associated with.
	 * @param mysqli $db Database connection object
	 * @param string $address Email address to look up
	 * @return ?int Player ID linked to the email, or null if none
	 * @throws DatabaseException Thrown when the database is unable to complete a request
	 */
	public static function UsedBy(mysqli $db, string $address): ?int {
		try {
			$select = $db->prepare('select player from email where address=? limit 1');
			$select->bind_param('s', $address);
			$select->execute();
			$select->bind_result($player);
			if ($select->fetch()) {
				$select->close();
				return $player;
			} else {
				$select->close();
				return null;
			}
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up email address', $mse);
		}
	}

	/**
	 * Clear the primary flag from all emails for the specified player.  This
	 * should be followed by adding a new email for that player with the primary
	 * flag set, or setting the primary flag on an existing email.
	 * @param mysqli $db Database connection object
	 * @param int $player Player ID whose emails should be affected
	 * @throws DatabaseException Thrown when the database isn't able to complete a request
	 */
	private static function ClearPrimary(mysqli $db, int $player): void {
		try {
			$update = $db->prepare('update email set isPrimary=0 where player=?');
			$update->bind_param('i', $player);
			$update->execute();
			$update->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error clearing primary flag from other email addresses', $mse);
		}
	}
}
