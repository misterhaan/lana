<?php

/**
 * Players link their email address(es) so other players can find them, and set
 * one of those as the address LANA should use to contact them.
 */
class Email {
	public string $address;
	public bool $isPrimary;

	private function __construct(string $address, bool $isPrimary) {
		$this->address = $address;
		$this->isPrimary = $isPrimary;
	}

	/**
	 * List the email addresses linked to a player.
	 * @param mysqli $db Database connection object
	 * @param int $player Player ID whose emails should be listed
	 * @return self[] List of email addresses linked to the player
	 */
	public static function List(mysqli $db, int $player): array {
		try {
			$select = $db->prepare('select address, isPrimary from email where player=? order by isPrimary desc, address');
			$select->bind_param('i', $player);
			$select->execute();
			/** @var bool $isPrimary */
			$select->bind_result($address, $isPrimary);
			$emails = [];
			while ($select->fetch())
				$emails[] = new self($address, $isPrimary);
			$select->close();
			return $emails;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error listing email addresses', $mse);
		}
	}

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

	/**
	 * Set an email address as the primary email for a player.  The email must
	 * already be linked to the player.
	 * @param mysqli $db Database connection object
	 * @param int $player Player ID whose primary email should be set
	 * @param string $email Email address to set as primary
	 * @throws DatabaseException Thrown when the database isn't able to complete a request, or if the email isn't linked to the player
	 */
	public static function SetPrimary(mysqli $db, int $player, string $email): void {
		$linked = self::UsedBy($db, $email);
		if ($linked != $player)
			throw new DatabaseException('Email address is not linked to you');
		try {
			$db->begin_transaction();
			self::ClearPrimary($db, $player);
			$update = $db->prepare('update email set isPrimary=1 where player=? and address=?');
			$update->bind_param('is', $player, $email);
			$update->execute();
			$update->close();
			$db->commit();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error setting primary email address', $mse);
		}
	}

	/**
	 * Remove an email address linked to a player.  The email address must not be the primary email for the player.
	 * @param mysqli $db Database connection object
	 * @param int $player Player ID whose email should be removed
	 * @param string $email Email address to remove
	 */
	public static function Remove(mysqli $db, int $player, string $email): void {
		try {
			$delete = $db->prepare('delete from email where player=? and address=? and not isPrimary');
			$delete->bind_param('is', $player, $email);
			$delete->execute();
			$delete->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error removing email address', $mse);
		}
	}
}
