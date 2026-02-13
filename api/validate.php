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
	 * Validate a username.  Provide the username as the request body.
	 */
	protected static function POST_username(): void {
		$username = trim(self::ReadRequestText());
		if (!$username)
			self::NeedMoreInfo('Username must be included in the request body.');
		require_once CLASS_PATH . 'player.php';
		if (!PlayerOne::ValidUsername($username))
			self::Invalid('Must be between 4 and 20 characters and cannot include / # ? or spaces');
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
	 * Validate an email address.  Provide the email address as the request body.
	 */
	protected static function POST_email(): void {
		$address = trim(self::ReadRequestText());
		if (!$address)
			self::NeedMoreInfo('Email address must be included in the request body.');
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
	 * Validate a link URL the player intends to add to their profile.  Provide the URL as the request body.
	 */
	protected static function POST_addLink(): void {
		$url = trim(self::ReadRequestText());
		if (!$url)
			self::NeedMoreInfo('Link URL must be included in the request body.');
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		require_once CLASS_PATH . 'profile.php';
		$error = ProfileSettings::ValidateUrl($db, $player, $url);
		if ($error)
			self::Invalid($error);
		self::Valid('Link URL is valid and not already on your profile');
	}
}
ValidateApi::Respond();
