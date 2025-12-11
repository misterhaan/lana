<?php
if (!defined('CLASS_PATH'))
	define('CLASS_PATH', dirname(__DIR__) . '/etc/class/');
require_once CLASS_PATH . 'api.php';
require_once CLASS_PATH . 'player.php';

/**
 * Handler for player API requests.
 * @author misterhaan
 */
class PlayerApi extends Api {
	/**
	 * Get the list of registered players.
	 */
	protected static function GET_list(): void {
		self::Success(Player::List(self::RequireLatestDatabase()));
	}

	/**
	 * Get the profile of the requested player.
	 * @param array $params - username to look up
	 */
	protected static function GET_profile(array $params): void {
		$playerName = trim(array_shift($params));
		self::Success(PlayerProfile::FromName(self::RequireLatestDatabase(), $playerName));
	}
}
PlayerApi::Respond();
