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
		self::Success(IndexPlayer::List(self::RequireLatestDatabase()));
	}
}
PlayerApi::Respond();
