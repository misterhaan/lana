<?php
if (!defined('CLASS_PATH'))
	define('CLASS_PATH', dirname(__DIR__) . '/etc/class/');
require_once CLASS_PATH . 'api.php';
require_once CLASS_PATH . 'auth.php';

/**
 * Handler for auth API requests.
 * @author misterhaan
 */
class AuthApi extends Api {
	/**
	 * Get the list of authentication sites.
	 */
	protected static function GET_list(): void {
		self::Success(AuthController::Sites);
	}

	/**
	 * Get the currently signed-in player, or null.
	 */
	protected static function GET_player(): void {
		self::Success(self::RequirePlayer(self::RequireLatestDatabase(), true));
	}

	/**
	 * Get the sign in URL for the specified authentication site.
	 * @param array $params The first parameter must be the ID of an authentication site defined in AuthController::Sites
	 */
	protected static function GET_url(array $params): void {
		$siteId = trim(array_shift($params));
		if (!$siteId)
			self::NeedMoreInfo('Authentication site ID must be included in the request URL such as auth/url/{siteId}.');
		$auth = AuthController::GetAuth($siteId);
		if (!$auth)
			self::NotFound("Site ID $siteId is not defined.  Check auth/list for valid site ID values.");
		self::RequireAuthSiteKeys();
		$remember = isset($_GET['remember']) && strtolower($_GET['remember']) != 'false' && $_GET['remember'] != '0';
		$returnHash = isset($_GET['returnHash']) && $_GET['returnHash'][0] == '#' ? $_GET['returnHash'] : '';
		self::Success($auth->GetUrl($remember, $returnHash));
	}

	/**
	 * Register a new player based on a previous a previous signin call that did
	 * not match an existing player.
	 * @param array $params The first parameter must be the ID of an authentication site defined in AuthController::Sites
	 */
	protected static function POST_register($params): void {
		$siteId = trim(array_shift($params));
		if (!$siteId)
			self::NeedMoreInfo('Authentication site ID must be included in the request URL such as auth/register/{siteId}.');
		$auth = AuthController::GetAuth($siteId);
		if (!$auth)
			self::NotFound("Site ID $siteId is not defined.  Check auth/list for valid site ID values.");
		$account = $auth->GetCachedAccount();
		if (!$account)
			self::NeedMoreInfo('Unable to save new sign-in because authenticating site account information invalid or not found on LANA server.');
		if (!isset($_POST['username']) || !($username = trim($_POST['username'])))
			self::NeedMoreInfo('Required parameters are missing or blank.');
		$realName = isset($_POST['realName']) ? trim($_POST['realName']) : '';
		$email = isset($_POST['email']) ? trim($_POST['email']) : '';
		if (!preg_match('/^[^@]+@[^.@]+(\.[^@.]+)+$/', $email))
			$email = '';
		$avatar = isset($_POST['avatar']) ? trim($_POST['avatar']) : 'account';
		if ($avatar == 'account' && !$account->avatarUrl)
			$avatar = 'email';
		if ($avatar == 'email' && !$email)
			$avatar = 'default';
		$db = self::RequireLatestDatabase();
		$db->begin_transaction();  // don't make any database changes unless we can make them all
		require_once CLASS_PATH . 'player.php';
		$player = Player::FromRegister($db, $username, $realName);
		$player->AddAccount($db, $auth->id, $account, $avatar == 'account');
		if ($email)
			$player->AddEmail($db, $email, true, $avatar == 'email');
		if ($account->remember) {
			require_once CLASS_PATH . 'cookie.php';
			Cookie::Remember($db, $player->id);
		}
		$db->commit();
		self::Success();
	}

	/**
	 * Attempt to sign in based on external site authentication.
	 * @param array $params The first parameter must be the ID of an authentication site defined in AuthController::Sites
	 */
	protected static function POST_signin($params): void {
		$siteId = trim(array_shift($params));
		if (!$siteId)
			self::NeedMoreInfo('Authentication site ID must be included in the request URL such as auth/signin/{siteId}.');
		$auth = AuthController::GetAuth($siteId);
		if (!$auth)
			self::NotFound("Site ID $siteId is not defined.  Check auth/list for valid site ID values.");
		self::RequireAuthSiteKeys();
		try {
			$result = $auth->Authenticate();
			$db = self::RequireLatestDatabase();
			$currentPlayer = self::RequirePlayer($db, true);
			$newPlayer = Player::FromAuth($db, $auth->id, $result->accountId, $result->username, $result->profileUrl, $result->avatarUrl);
			if ($newPlayer) {
				if ($currentPlayer && $newPlayer->id != $currentPlayer->id)
					self::DatabaseError('Unable to link account because it as already linked to another player');
				if (!$currentPlayer && $result->remember) {
					require_once CLASS_PATH . 'cookie.php';
					Cookie::Remember($db, $newPlayer->id);
				}
				self::Success([
					'registered' => true,
					'returnHash' => $result->returnHash
				]);
			} elseif ($currentPlayer) {
				$currentPlayer->AddAccount($db, $auth->id, $result);
				self::Success([
					'registered' => true,
					'returnHash' => $result->returnHash
				]);
			} else {
				$auth->CacheResult($result);
				self::Success([
					'registered' => false,
					'returnHash' => $result->returnHash,
					'siteName' => $auth->name,
					'username' => $result->username,
					'realName' => $result->realName,
					'email' => $result->email,
					'avatar' => $result->avatarUrl,
					'profile' => $result->profileUrl
				]);
			}
		} catch (AuthenticationException $ae) {
			self::DatabaseError($ae->getMessage());
		}
	}

	/**
	 * Forget sign in information.
	 */
	protected static function POST_signout(): void {
		require_once CLASS_PATH . 'player.php';
		Player::Signout(self::RequireLatestDatabase());
		self::Success();
	}
}
AuthApi::Respond();
