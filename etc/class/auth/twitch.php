<?php
if(!defined('CLASS_PATH'))
	define('CLASS_PATH', dirname(__DIR__) . '/');

require_once CLASS_PATH . 'auth.php';

/**
 * Authorization implementation for twitch.tv, based on documentation at
 * https://dev.twitch.tv/docs/authentication/getting-tokens-oidc#oidc-authorization-code-flow
 **/
class TwitchAuth extends Auth {
	/**
	 * Get the sign in URL for Twitch.
	 * @param bool $remember True if the user wants LANA to remember who they are
	 * @param string $returnHash Location hash (starting with #) to return to after sign in
	 * @return string URL for signing in via Twitch
	 */
	public function GetUrl(bool $remember, string $returnHash) {
		$state = $remember ? 'remember' : '';
		if($returnHash)
			$state .= ($state ? '&' : '') . 'returnHash=' . urlencode($returnHash);
		$data = [
			'claims' => json_encode(['id_token' => ['email' => null, 'picture' => null, 'preferred_username' => null]]),
			'client_id' => KeysTwitch::ClientId,
			'nonce' => $this->GenerateNonce(),
			'redirect_uri' => $this->GetRedirectUrl(),
			'response_type' => 'code',
			'scope' => 'openid user:read:email',  // the only non-basic permission we want is the user's email address
			'state' => $state
		];
		return 'https://id.twitch.tv/oauth2/authorize?' . http_build_query($data);
	}

	/**
	 * Authenticate response from Twitch.
	 * @return AuthenticationResult Result of authentication attempt
	 * @throws AuthenticationException Thrown when unable to authenticate
	 */
	public function Authenticate() {
		if(isset($_POST['code']) && $code = trim($_POST['code'])) {
			$remember = false;
			$returnHash = '';
			if(isset($_POST['state'])) {
				parse_str($_POST['state'], $state);
				$remember = isset($state['remember']);
				if(isset($state['returnHash']) && $state['returnHash'].substr(0, 1) == '#')
					$returnHash = $state['returnHash'];
			}
			$tokens = $this->GetTokens($code);
			if(isset($tokens->id_token) && $idToken = $tokens->id_token) {
				$id = explode('.', $idToken);
				$id = json_decode(base64_decode($id[1]));
				if(isset($id->nonce) && self::ValidateNonce($id->nonce))
					return new AuthenticationResult(
						$remember, $returnHash, trim($id->sub), trim($id->email), trim($id->preferred_username), '', trim($id->picture),
						'https://www.twitch.tv/' . trim($id->preferred_username)
					);
				else
					throw new AuthenticationException('Unable to validate ID token — authentication failed');
			} else
				throw new AuthenticationException('Response from Twitch did not include id_token — authentication failed');
		} else
			throw new AuthenticationException('Code not present — cannot request ID token');
	}

	/**
	 * Send auth code to twitch to get access and ID tokens.
	 * @param string $code Auth code that should have come from twitch but needs to be verified
	 * @return object Object containing access token and ID token
	 */
	private function GetTokens(string $code) {
		$data = [
			'client_id' => KeysTwitch::ClientId,
			'client_secret' => KeysTwitch::ClientSecret,
			'code' => $code,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $this->GetRedirectUrl()
		];
		$response = self::PostRequest('https://id.twitch.tv/oauth2/token', $data);
		$tokens = json_decode($response);
		return $tokens;
	}
}
