<?php
if(!defined('CLASS_PATH'))
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
	protected static function GET_username(array $params) {
		if(isset($params) && isset($params[0]) && $username = trim($params[0])) {
			require_once CLASS_PATH . 'player.php';
			if(Player::ValidUsername($username)) {
				$db = self::RequireLatestDatabase();
				if($used = Player::UsernameUsedBy($db, $username)) {
					$player = self::RequirePlayer($db, true);
					if($player && $player->id == $used)
						self::Valid('This is your current username');
					else
						self::Invalid('Username already in use');
				} else
					self::Valid('Username available');
			} else
				self::Invalid('Must be between 4 and 20 characters');
		} else
			self::NeedMoreInfo('Username must be included in the request URL such as validate/username/{username}.');
	}
	/**
	 * Validate an email address.
	 * @param array $params The first parameter must be the email address to validate
	 */
	protected static function GET_email(array $params) {
		if(isset($params) && isset($params[0]) && $address = trim($params[0])) {
			require_once CLASS_PATH . 'email.php';
			if(Email::Valid($address)) {
				$db = self::RequireLatestDatabase();
				if($linked = Email::UsedBy($db, $address)) {
					$player = self::RequirePlayer($db, true);
					if($player && $player->id == $linked)
						self::Valid('This is your current email address');
					else
						self::Invalid('Email address already linked to another player');
				} else
					self::Valid('Email address available');
			} else
				self::Invalid('Does not look like an email address');
		} else
			self::NeedMoreInfo('Email address must be included in the request URL such as validate/email/{emailAddress}.');
	}

	/**
	 * Report a valid result message.  Ends script execution.
	 * @param string $message Validation message to report
	 */
	private static function Valid(string $message) {
		self::Success((object)[
			'status' => 'valid',
			'message' => $message
		]);
	}

	/**
	 * Report an invalid result message.  Ends script execution.
	 * @param string $message Validation message to report
	 */
	private static function Invalid(string $message) {
		self::Success((object)[
			'status' => 'invalid',
			'message' => $message
		]);
	}
}
ValidateApi::Respond();
