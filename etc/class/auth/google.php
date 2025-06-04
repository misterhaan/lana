<?php
if (!defined('CLASS_PATH'))
	define('CLASS_PATH', dirname(__DIR__) . '/');

require_once CLASS_PATH . 'auth.php';

/**
 * Authorization implementation for google, based on documentation at
 *
 **/
class GoogleAuth extends Auth {
	/**
	 * Get the sign in URL for Google.
	 * @param bool $remember True if the user wants LANA to remember who they are
	 * @param string $returnHash Location hash (starting with #) to return to after sign in
	 * @return string URL for signing in via Google
	 */
	public function GetUrl(bool $remember, string $returnHash): string {

		$state = $remember ? 'remember' : '';
		if ($returnHash)
			$state .= ($state ? '&' : '') . 'returnHash=' . urlencode($returnHash) . '&nonce=' . $this->GenerateNonce();
		$data = [
			'client_id' => KeysGoogle::ClientId,
			'redirect_uri' => $this->GetRedirectUrl(),
			'response_type' => 'code',
			'scope' => 'openid email profile',
			'state' => $state
		];
		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($data);
	}

	/**
	 * Authenticate response from Google.
	 * @return AuthenticationResult Result of authentication attempt
	 * @throws AuthenticationException Thrown when unable to authenticate
	 */
	public function Authenticate(): AuthenticationResult {
		if (!isset($_POST['code']))
			throw new AuthenticationException('Code not present — cannot request ID token');
		if (!isset($_POST['state']))
			throw new AuthenticationException('State not present — cannot validate nonce');
		parse_str($_POST['state'], $state);
		if (!isset($state['nonce']) || !self::ValidateNonce($state['nonce']))
			throw new AuthenticationException('Nonce not present or invalid — cannot validate ID token');

		$id = $this->GetIdToken($_POST['code']);

		$remember = isset($state['remember']);
		$returnHash =  (isset($state['returnHash']) && substr($state['returnHash'], 0, 1) == '#') ? $state['returnHash'] : '';

		return new AuthenticationResult(
			$remember,
			$returnHash,
			trim($id->sub),
			trim($id->email),
			explode('@', $id->email)[0],
			trim($id->name),
			trim($id->picture),
			'mailto:' . trim($id->email)
		);
	}

	private function GetIdToken(string $code): object {
		$data = [
			'code' => $code,
			'client_id' => KeysGoogle::ClientId,
			'client_secret' => KeysGoogle::ClientSecret,
			'redirect_uri' => $this->GetRedirectUrl(),
			'grant_type' => 'authorization_code'
		];
		$tokenJson = $this->PostRequest('https://oauth2.googleapis.com/token', $data);
		$token = json_decode($tokenJson);
		$id = explode('.', $token->id_token);
		return json_decode(base64_decode($id[1]));
	}
}
