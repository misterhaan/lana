<?php
/**
 * The authentication controller class is the entry point into the site-specific
 * authentication classes.
 * @author misterhaan
 **/
class AuthController {
	/**
	 * Supported authentication sites.  Each must have a {Name}Auth class defined
	 * at auth/{id}.php
	 **/
	public const Sites = [
		[
			'id' => 'twitch',
			'name' => 'Twitch'
		]
	];

	/**
	 * Get the authentication object for the requested site.
	 * @param string $siteId ID of authentication site.  Must be present in self::Sites.
	 * @return Auth|bool Authentication object or FALSE if $siteId is not valid.
	 **/
	public static function GetAuth(string $siteId) {
		$site = self::FindSite($siteId);
		if($site) {
			require_once "auth/$site->id.php";
			$class = $site->name . 'Auth';
			$auth = new $class();
			$auth->id = $site->id;
			$auth->name = $site->name;
			return $auth;
		}
		return false;
	}

	/**
	 * Lookup authentication site information based on a site ID.
	 * @param string $id ID of authentication site.
	 * @return object|bool Object of authentication site information or FALSE if $id is not valid.
	 **/
	public static function FindSite(string $id) {
		$id = strtolower($id);
		foreach(self::Sites as $site)
			if($site['id'] == $id)
				return (object)$site;
		return false;
	}
}

/**
 * Base class for site-specific authentication classes, which are found in
 * auth/{id}.php for each Site defined in AuthController::Sites.
 * @author misterhaan
 **/
abstract class Auth {
	/**
	 * Get the URL that will allow the user to authenticate through the external
	 * site.
	 * @param bool $remember True if the user wants an auto-signin cookie set after successful authentication
	 * @param string $returnHash Location hash (starting with #) to return to after sign in
	 * @return string Authentication URL on external site
	 **/
	public abstract function GetUrl(bool $remember, string $returnHash);

	/**
	 * Authenticate response from external authentication site.
	 * @return AuthenticationResult Result of authentication attempt
	 * @throws AuthenticationException Thrown when something prevents authentication
	 */
	public abstract function Authenticate();

	/**
	 * Cache an authorized account to be used at registration.
	 * @param AuthenticationAccount $account Authorized account information
	 */
	public function CacheResult(AuthenticationAccount $account) {
		$_SESSION['authSite'] = $this->id;
		$_SESSION['authAccount'] = $account->accountId;
		$_SESSION['authName'] = $account->username;
		$_SESSION['authAvatar'] = $account->avatarUrl;
		$_SESSION['authProfile'] = $account->profileUrl;
		$_SESSION['authRemember'] = $account->remember;
	}

	/**
	 * Retrieve an authorized account from the cache.
	 */
	public function GetCachedAccount() {
		$account = false;
		if(isset($_SESSION['authSite'], $_SESSION['authAccount'])) {
			if($_SESSION['authSite'] == $this->id) {
				$account = new AuthenticationAccount(
					$_SESSION['authAccount'],
					$_SESSION['authName'],
					$_SESSION['authAvatar'],
					$_SESSION['authProfile'],
					$_SESSION['authRemember']
				);
			}
			unset($_SESSION['authSite'], $_SESSION['authAccount'], $_SESSION['authName'], $_SESSION['authAvatar'], $_SESSION['authProfile'], $_SESSION['authRemember']);
		}
		return $account;
	}

	/**
	 * After login, redirect to this URL with the site ID appended to the end
	 **/
	private const AfterLoginUrl = '/signin-';

	/**
	 * Get the URL that external login should redirect to.
	 * @return string Redirect URL for after external login.
	 */
	protected function GetRedirectUrl() {
		require_once 'url.php';
		return Url::FullUrl(self::AfterLoginUrl . $this->id);
	}

	/**
	 * Generate a nonce value to ensure that an authentication response is
	 * expected.  Overwrites previous nonce if there was one, even if it was
	 * generated for a different authentication site.
	 * @return string One-time use (nonce) authentication identifier
	 **/
	protected function GenerateNonce() {
		$nonce = bin2hex(openssl_random_pseudo_bytes(16));
		$_SESSION['nonce'] = $nonce;
		$_SESSION['expectedAuthSource'] = $this->id;
		return $nonce;
	}

	/**
	 * Check a nonce against the last one generated and check the authentication
	 * site against the one that generate the last nonce.  Clears the saved
	 * values, which means another check of the same value will fail.
	 * @param string $nonce Nonce value that came back with an authenication response
	 * @return bool True if nonce and authentication site match the last generated nonce.
	 **/
	protected function ValidateNonce(string $nonce) {
		if(isset($_SESSION['nonce'], $_SESSION['expectedAuthSource'])) {
			$chkNonce = $_SESSION['nonce'];
			$chkSource = $_SESSION['expectedAuthSource'];
			unset($_SESSION['nonce'], $_SESSION['expectedAuthSource']);
			return $nonce == $chkNonce && $this->id == $chkSource;
		}
		return false;
	}

	/**
	 * Execute an HTTP POST request to a specified URL with the provided data.
	 * @param string $url Absolute URL to request
	 * @param array $data Associative array of data to include with the request
	 * @return string HTTP response
	 **/
	protected function PostRequest(string $url, array $data) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['SERVER_NAME']);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
}

/**
 * Information on an authentication account.
 */
class AuthenticationAccount {
	/**
	 * ID for the account at the external site
	 */
	public $accountId;

	/**
	 * Username associated with the account
	 */
	public $username;

	/**
	 * URL to the avatar of the account
	 */
	public $avatarUrl;

	/**
	 * URL to the profile of the account
	 */
	public $profileUrl;

	/**
	 * Whether to set a cookie to remember this sign in
	 */
	public $remember;

	/**
	 * Create an AuthenticationResult and set its properties.
	 * @param string $accountId ID for the account at the external site
	 * @param string $username Username associated with the account
	 * @param string $avatarUrl URL to the avatar of the account
	 * @param string $profileUrl URL to the profile of the account
	 * @param bool $remember True if the player requested an autosignin cookie
	 */
	public function __construct(string $accountId, string $username = '', string $avatarUrl = '', string $profileUrl = '', bool $remember = false) {
		$this->accountId = $accountId;
		$this->email = $email;
		$this->username = $username;
		$this->avatarUrl = $avatarUrl;
		$this->profileUrl = $profileUrl;
		$this->remember = $remember;
	}
}

/**
 * Result of a successful authentication.
 */
class AuthenticationResult extends AuthenticationAccount {
	/**
	 * Address hash of LANA to return to after sign in
	 */
	public $returnHash;

	/**
	 * Real name associated with the account
	 */
	public $realName;

	/**
	 * Email address associated with the account
	 */
	public $email;

	/**
	 * Create an AuthenticationResult and set its properties.
	 * @param bool $remember Whether to set a cookie to remember this sign in
	 * @param string $returnHash Address hash of LANA to return to after sign in
	 * @param string $accountId ID for the account at the external site
	 * @param string $email Email address associated with the account
	 * @param string $username Username associated with the account
	 * @param string $realName Real name associated with the account
	 * @param string $avatarUrl URL to the avatar of the account
	 * @param string $profileUrl URL to the profile of the account
	 */
	public function __construct(bool $remember, string $returnHash, string $accountId, string $email = '', string $username = '', string $realName = '', string $avatarUrl = '', string $profileUrl = '') {
		$this->remember = $remember;
		$this->returnHash = $returnHash;
		$this->accountId = $accountId;
		$this->email = $email;
		$this->username = $username;
		$this->realName = $realName;
		$this->avatarUrl = $avatarUrl;
		$this->profileUrl = $profileUrl;
	}
}

/**
 * Thrown when something prevents authentication.
 */
class AuthenticationException extends Exception {}
