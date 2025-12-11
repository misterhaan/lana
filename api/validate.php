<?php
if (!defined('CLASS_PATH'))
	define('CLASS_PATH', dirname(__DIR__) . '/etc/class/');
require_once CLASS_PATH . 'api.php';

/**
 * Handler for validate API requests.
 * @author misterhaan
 */
class ValidateApi extends Api {
	/**
	 * Validate a username.
	 * @param array $params The first parameter must be the username to validate
	 */
	protected static function GET_username(array $params): void {
		$username = trim(array_shift($params));
		if (!$username)
			self::NeedMoreInfo('Username must be included in the request URL such as validate/username/{username}.');
		require_once CLASS_PATH . 'player.php';
		if (!PlayerOne::ValidUsername($username))
			self::Invalid('Must be between 4 and 20 characters');
		$db = self::RequireLatestDatabase();
		$used = PlayerOne::UsernameUsedBy($db, $username);
		if (!$used)
			self::Valid('Username available');
		$player = self::RequirePlayer($db, true);
		if ($player && $player->id == $used)
			self::Valid('This is your current username');
		else
			self::Invalid('Username already in use');
	}
	/**
	 * Validate an email address.
	 * @param array $params The first parameter must be the email address to validate
	 */
	protected static function GET_email(array $params): void {
		$address = trim(array_shift($params));
		if (!$address)
			self::NeedMoreInfo('Email address must be included in the request URL such as validate/email/{emailAddress}.');
		require_once CLASS_PATH . 'email.php';
		if (!Email::Valid($address))
			self::Invalid('Does not look like an email address');
		$db = self::RequireLatestDatabase();
		$linked = Email::UsedBy($db, $address);
		if (!$linked)
			self::Valid('Email address available');
		$player = self::RequirePlayer($db, true);
		if ($player && $player->id == $linked)
			self::Valid('This is your current email address');
		else
			self::Invalid('Email address already linked to another player');
	}

	/**
	 * Report a valid result message.  Ends script execution.
	 * @param string $message Validation message to report
	 */
	private static function Valid(string $message): void {
		self::Success((object)[
			'status' => 'valid',
			'message' => $message
		]);
	}

	/**
	 * Report an invalid result message.  Ends script execution.
	 * @param string $message Validation message to report
	 */
	private static function Invalid(string $message): void {
		self::Success((object)[
			'status' => 'invalid',
			'message' => $message
		]);
	}
}
ValidateApi::Respond();
