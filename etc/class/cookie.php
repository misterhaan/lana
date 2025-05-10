<?php
class Cookie {
	private const Name = 'player';
	private const ExpireDays = 30;

	/**
	 * Remember a player sign-in with a secure cookie.
	 * @param mysqli $db Database connection object
	 * @param int $player ID of player to remember
	 */
	public static function Remember(mysqli $db, int $player) {
		$series = self::StartSeries($db);
		self::CreateToken($db, $player, $series);
	}

	/**
	 * Look up the player for an autosignin cookie.  Removes cookie if invalid or
	 * expired.
	 * @param mysqli $db Database connection object
	 * @return int|bool Player ID from verified token, or false if unable to verify
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	public static function Verify(mysqli $db) {
		if (isset($_COOKIE[self::Name]) && $cookie = trim($_COOKIE[self::Name])) {
			$cookie = explode(':', $cookie);
			if (count($cookie) == 2) {
				$series = $cookie[0];
				$token = base64_decode($cookie[1]);
				if ($get = $db->prepare('select tokenHash, expires>now(), player as active from cookie where series=? limit 1'))
					if ($get->bind_param('s', $series))
						if ($get->execute())
							if ($get->bind_result($tokenHash, $active, $player)) {
								if ($get->fetch()) {
									$get->close();
									$get = false;
									if (+$active && $tokenHash == base64_encode(hash('sha512', $token, true))) {
										self::CreateToken($db, +$player, $series);  // successful login means we need a fresh token for next time
										return +$player;
									} else
										self::DeleteSeries($db, $series);  // expired or incorrect token means we should delete it
								}
							} else
								throw new DatabaseException('Error binding cookie look up result', $get);
						else
							throw new DatabaseException('Error executing cookie look up query', $get);
					else
						throw new DatabaseException('Error binding series identifier to look up cookie', $get);
				else
					throw new DatabaseException('Error preparing to look up cookie', $db);
			}
			require_once 'url.php';
			setcookie(self::Name, '', time() - 3600, Url::InstallPath() . '/', $_SERVER['SERVER_NAME'], true);
		}
		return false;
	}

	/**
	 * Forget an autosignin series from both the browser and the database when
	 * the user has signed out.  Called even if there is no autosignin series.
	 * @param mysqli $db Database connection object
	 */
	public static function Forget(mysqli $db) {
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
	 * @return string New series number, or false if unable to generate one
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	private static function StartSeries(mysqli $db) {
		if ($chk = $db->prepare('select 1 from cookie where series=? limit 1'))
			if ($chk->bind_param('s', $series)) {
				do {
					$chk->free_result();
					$series = base64_encode(openssl_random_pseudo_bytes(12));
					if ($chk->execute())
						$chk->store_result();
					else
						throw new DatabaseException('Error executing check for duplicate series ID', $chk);
				} while ($chk->num_rows > 0);
				$chk->close();
				return $series;
			} else
				throw new DatabaseException('Error binding series ID for duplicate check', $chk);
		else
			throw new DatabaseException('Error preparing to check for duplicate series ID', $db);
	}

	/**
	 * Create a new automatic login token and save it to the database and a cookie.
	 * @param mysqli $db Database connection object
	 * @param int $player ID of player requesting the token
	 * @param string $series Login series identifier
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	private static function CreateToken(mysqli $db, int $player, string $series) {
		$token = openssl_random_pseudo_bytes(32);
		$tokenHash = base64_encode(hash('sha512', $token, true));
		$token = base64_encode($token);
		$expireDays = self::ExpireDays;
		if ($put = $db->prepare('replace into cookie (series, tokenHash, expires, player) values (?, ?, now() + interval ? day, ?)')) {
			if ($put->bind_param('ssii', $series, $tokenHash, $expireDays, $player))
				if ($put->execute()) {
					require_once 'url.php';
					setcookie(self::Name, $series . ':' . $token, time() + self::ExpireDays * 86400, Url::InstallPath() . '/', $_SERVER['SERVER_NAME'], true);
				} else
					throw new DatabaseException('Error saving cookie', $put);
			else
				throw new DatabaseException('Error binding cookie parameters', $put);
			$put->close();
		} else
			throw new DatabaseException('Error preparing to save cookie', $db);
	}

	/**
	 * Delete a login series from the database.  Called when it's found to have
	 * expired or a non-matching token is encountered.  Also deletes other
	 * expired series.
	 * @param mysqli $db Database connection object
	 * @param string $series Series identifier to delete
	 * @throws DatabaseException Thrown when the database cannot complete a request
	 */
	private static function DeleteSeries(mysqli $db, string $series) {
		if ($del = $db->prepare('delete from cookie where series=? or expires<now()'))
			if ($del->bind_param('s', $series))
				if ($del->execute())
					$del->close();
				else
					throw new DatabaseException('Error deleting cookies', $del);
			else
				throw new DatabaseException('Error binding series ID for cookie deletion', $del);
		else
			throw new DatabaseException('Error preparing to delete cookies', $db);
	}
}
