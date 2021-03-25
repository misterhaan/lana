<?php
if(!defined('CLASS_PATH'))
	define('CLASS_PATH', dirname(__DIR__) . '/');

require_once CLASS_PATH . 'auth.php';

/**
 * Authorization implementation for steamcommunity.com, which almost has
 * documentation at
 * https://partner.steamgames.com/doc/features/auth#website
 * Also a player summary API documented at
 * https://developer.valvesoftware.com/wiki/Steam_Web_API#GetPlayerSummaries_.28v0002.29
 **/
class SteamAuth extends Auth {
	private const OpenIdNamespace = 'http://specs.openid.net/auth/2.0';
	private const Login = 'https://steamcommunity.com/openid/login';
	private const Profile = 'https://steamcommunity.com/profiles/';
	private const OpenIdIdentifier = 'http://specs.openid.net/auth/2.0/identifier_select';

	/**
	 * Get the sign in URL for Steam.
	 * @param bool $remember True if the user wants LANA to remember who they are
	 * @param string $returnHash Location hash (starting with #) to return to after sign in
	 * @return string URL for signing in via Steam
	 */
	public function GetUrl(bool $remember, string $returnHash) {
		require_once CLASS_PATH . 'url.php';
		$returnParams = [
			'nonce' => self::GenerateNonce()
		];
		if($returnHash && $returnHash != '#')
			$returnParams['returnHash'] = $returnHash;
		$requestParams = [
			'openid.ns' => self::OpenIdNamespace,
			'openid.mode' => 'checkid_setup',
			'openid.return_to' => $this->GetRedirectUrl() . '?' . ($remember ? 'remember&': '') . http_build_query($returnParams),
			'openid.realm' => Url::FullUrl('/'),
			'openid.identity' => self::OpenIdIdentifier,
			'openid.claimed_id' => self::OpenIdIdentifier
		];
		return self::Login . '?' . http_build_query($requestParams);
	}

	/**
	 * Authenticate response from Steam.
	 * @return AuthenticationResult Result of authentication attempt
	 * @throws AuthenticationException Thrown when unable to authenticate
	 */
	public function Authenticate() {
		// Note that even thought the variable names are sent formatted like openid.signed,
		// PHP doesn't allow dots and changes them to underscores, resulting in names
		// like openid_signed.  Since the original names also include underscores, there's
		// no way to change them back so this code just uses them with underscores.
		if(isset($_POST['openid_claimed_id']))
			if(isset($_POST['openid_assoc_handle'], $_POST['openid_signed'], $_POST['openid_sig']))
				if(isset($_POST['nonce']) && self::ValidateNonce($_POST['nonce']) && $this->Validate()) {
					$id = explode('/', $_POST['openid_claimed_id']);
					$id = $id[count($id) - 1];
					$remember = isset($_POST['remember']);
					$returnHash = isset($_POST['returnHash']) && substr($_POST['returnHash'], 0, 1) == '#' ? $_POST['returnHash'] : '';
					$info = $this->GetPlayerInfo($id);
					return new AuthenticationResult($remember, $returnHash, $id, '', $info->username, $info->realName, $info->avatar, $info->profile);
				} else
					throw new AuthenticationException('Unable to validate request origin — authentication failed');
			else
				throw new AuthenticationException('Missing openid signature information — cannot validate authentication');
		else
			throw new AuthenticationException('Claimed ID not present — cannot validate authentication');
	}

	/**
	 * Validate that a sign-in result actualy came from Steam.
	 * @return bool True if authentication information is valid
	 */
	private static function Validate() {
		$data = [
			'openid.assoc_handle' => $_POST['openid_assoc_handle'],
			'openid.signed' => $_POST['openid_signed'],
			'openid.sig' => $_POST['openid_sig'],
			'openid.ns' => self::OpenIdNamespace,
			'openid.mode' => 'check_authentication'
		];
		foreach(explode(',', $_POST['openid_signed']) as $var)
			$data['openid.' . $var] = $_POST['openid_' . str_replace('.', '_', $var)];
		$response = self::PostRequest(self::Login, $data);
		$resarr = [];
		foreach(explode("\n", $response) as $line) {
			$varval = explode(':', $line, 2);
			if(count($varval) == 2)
				$resarr[trim($varval[0])] = trim($varval[1]);
		}
		return isset($resarr['ns'], $resarr['is_valid']) && $resarr['ns'] == self::OpenIdNamespace && $resarr['is_valid'] == 'true';
	}

	/**
	 * Look up Steam profile information for the player with the specified ID.
	 * @param string $id SteamID64 to look up
	 * @return PlayerInfo Player info object
	 */
	private static function GetPlayerInfo(string $id) {
		$response = self::GetRequest(self::Profile . $id . '?xml=1');
		if($xml = simplexml_load_string($response))
			if(!$xml->error) {
				$realName = html_entity_decode((string)$xml->steamID);
				if(isset($xml->customURL) && (string)$xml->customURL) {
					$username = (string)$xml->customURL;
					$profile = 'https://steamcommunity.com/id/' . $username;
				} else {
					$username = $realName;
					$profile = self::Profile . $id;
				}
				$avatar = (string)$xml->avatarMedium;  // 64px
				return new PlayerInfo($username, $realName, $profile, $avatar);
			}
		return new PlayerInfo('', '', self::Profile . $id, '');
	}
}

/**
 * Basic information from a player profile.
 */
class PlayerInfo {
	/**
	 * Username of this account.
	 * @var string
	 */
	public $username;

	/**
	 * Real name of this account
	 * @var string
	 */
	public $realName;

	/**
	 * URL to this account's profile
	 * @var string
	 */
	public $profile;

	/**
	 * URL to this account's avatar
	 * @var string
	 */
	public $avatar;

	/**
	 * Create a player profile object.
	 * @param string $username Username of this account
	 * @param string $realName Real name of this account
	 * @param string $profile URL to this account's profile
	 * @param string $avatar URL to this account's avatar
	 */
	public function __construct(string $username, string $realName, string $profile, string $avatar) {
		$this->username = $username;
		$this->realName = $realName;
		$this->profile = $profile;
		$this->avatar = $avatar;
	}
}
