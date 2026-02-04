<?php
class Cookie {
	private const Name = 'player';
	private const ExpireDays = 30;

	/**
	 * Remember a player sign-in with a secure cookie.
	 * @param mysqli $db Database connection object
	 * @param int $player ID of player to remember
	 */
	public static function Remember(mysqli $db, int $player): void {
		$series = self::StartSeries($db);
		self::CreateToken($db, $player, $series);
	}

	/**
	 * Look up the player for an auto-signin cookie.  Removes cookie if invalid or
	 * expired.
	 * @param mysqli $db Database connection object
	 * @return ?int Player ID from verified token, or null if unable to verify
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Verify(mysqli $db): ?int {
		if (!isset($_COOKIE[self::Name]) || !($cookie = trim($_COOKIE[self::Name])))
			return null;
		$cookie = explode(':', $cookie);
		if (count($cookie) != 2)
			return null;
		$series = $cookie[0];
		$token = base64_decode($cookie[1]);
		try {
			$select = $db->prepare('select tokenHash, expires>now(), player from cookie where series=? limit 1');
			$select->bind_param('s', $series);
			$select->execute();
			/** @var int $player */
			$select->bind_result($tokenHash, $active, $player);
			if ($select->fetch()) {
				$select->close();
				if ($active && $tokenHash == base64_encode(hash('sha512', $token, true))) {
					self::CreateToken($db, $player, $series);  // successful login means we need a fresh token for next time
					return $player;
				}
				self::DeleteSeries($db, $series);  // expired or incorrect token means we should delete it
			}
			require_once 'url.php';
			setcookie(self::Name, '', time() - 3600, Url::InstallPath() . '/', $_SERVER['SERVER_NAME'], true);
			return null;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error looking up cookie', $mse);
		}
	}

	/**
	 * Forget an auto-signin series from both the browser and the database when
	 * the user has signed out.  Called even if there is no auto-signin series.
	 * @param mysqli $db Database connection object
	 */
	public static function Forget(mysqli $db): void {
		if (isset($_COOKIE[self::Name]) && $cookie = trim($_COOKIE[self::Name])) {
			$cookie = explode(':', $cookie);
			if (count($cookie) == 2)
				self::DeleteSeries($db, $cookie[0]);
			setcookie(self::Name, '', time() - 3600, Url::InstallPath() . '/', $_SERVER['SERVER_NAME'], true);
		}
	}

	/**
	 * Generate a new series number.  Should only be used when logging in with
	 * remember me checked.  Series number is guaranteed to be unique.
	 * @param mysqli $db Database connection object
	 * @return string New series number
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	private static function StartSeries(mysqli $db): string {
		try {
			$chk = $db->prepare('select 1 from cookie where series=? limit 1');
			$chk->bind_param('s', $series);
			do {
				$chk->free_result();
				$series = base64_encode(openssl_random_pseudo_bytes(12));
				$chk->execute();
				$chk->store_result();
			} while ($chk->num_rows > 0);
			$chk->close();
			return $series;
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error generating unique series ID', $mse);
		}
	}

	/**
	 * Create a new automatic login token and save it to the database and a cookie.
	 * @param mysqli $db Database connection object
	 * @param int $player ID of player requesting the token
	 * @param string $series Login series identifier
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	private static function CreateToken(mysqli $db, int $player, string $series): void {
		$token = openssl_random_pseudo_bytes(32);
		$tokenHash = base64_encode(hash('sha512', $token, true));
		$token = base64_encode($token);
		$expireDays = self::ExpireDays;
		try {
			$replace = $db->prepare('replace into cookie (series, tokenHash, expires, player) values (?, ?, now() + interval ? day, ?)');
			$replace->bind_param('ssii', $series, $tokenHash, $expireDays, $player);
			$replace->execute();
			require_once 'url.php';
			setcookie(self::Name, $series . ':' . $token, time() + self::ExpireDays * 86400, Url::InstallPath() . '/', $_SERVER['SERVER_NAME'], true);
			$replace->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error saving cookie', $mse);
		}
	}

	/**
	 * Delete a login series from the database.  Called when it's found to have
	 * expired or a non-matching token is encountered.  Also deletes other
	 * expired series.
	 * @param mysqli $db Database connection object
	 * @param string $series Series identifier to delete
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	private static function DeleteSeries(mysqli $db, string $series): void {
		try {
			$delete = $db->prepare('delete from cookie where series=? or expires<now()');
			$delete->bind_param('s', $series);
			$delete->execute();
			$delete->close();
		} catch (mysqli_sql_exception $mse) {
			throw new DatabaseException('Error deleting cookies', $mse);
		}
	}
}
