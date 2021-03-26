<?php
if(!defined('CLASS_PATH'))
	define('CLASS_PATH', dirname(__DIR__) . '/etc/class/');
require_once CLASS_PATH . 'api.php';

/**
 * Handler for settings API requests.
 * @author misterhaan
 */
class SettingsApi extends Api {
	/**
	 * Get the list of sign-in accounts for the current player.
	 */
	protected static function GET_accounts() {
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		self::Success($player->GetAccounts($db));
	}

	/**
	 * Delete a sign-in account for the current player.
	 * @param array $params Must have site ID for the first element and account ID for the second
	 */
	protected static function DELETE_account($params) {
		if(isset($params) && isset($params[0], $params[1]) && ($site = trim($params[0])) && ($id = trim($params[1]))) {
			$db = self::RequireLatestDatabase();
			$player = self::RequirePlayer($db);
			require_once CLASS_PATH . 'account.php';
			if($account = new Account($site, $id, $db))
				if($account->player == $player->id) {
					require_once CLASS_PATH . 'profile.php';
					$db->autocommit(false);
					Profile::Delete($db, $account->profile);
					Account::Delete($db, $site, $id);
					$db->commit();
					self::Success();
				} else
					self::DatabaseError("Cannot unlink $site account $id because it is linked to a different player.");
			else
				self::NotFound("Could not find $site account $id.");
		} else
			self::NeedMoreInfo('Authentication site ID and account ID must be included in the request URL such as settings/account/{siteId}/{accountId}.');
	}
}
SettingsApi::Respond();
