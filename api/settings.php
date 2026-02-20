<?php
if (!defined('CLASS_PATH'))
	define('CLASS_PATH', dirname(__DIR__) . '/etc/class/');
require_once CLASS_PATH . 'api.php';

/**
 * Handler for settings API requests.
 * @author misterhaan
 */
class SettingsApi extends Api {
	/**
	 * Update the current player's username to the value specified in the request body.  Returns validation status, and only a valid username will be set.
	 */
	protected static function PUT_username(): void {
		$username = trim(self::ReadRequestText());
		if (!$username)
			self::NeedMoreInfo('Username must be included in the request body.');
		require_once CLASS_PATH . 'player.php';
		if (!PlayerOne::ValidUsername($username))
			self::Invalid('Must be between 4 and 20 characters and cannot include / # ? or spaces');
		$db = self::RequireLatestDatabase();
		$used = PlayerOne::UsernameUsedBy($db, $username);
		$player = self::RequirePlayer($db);
		if ($used)
			if ($player->id == $used)
				self::Valid('This is your current username');
			else
				self::Invalid('Username already in use');
		$player->SetUsername($db, $username);
		self::Valid('Username updated');
	}

	/**
	 * Update the current player's real name to the value specified in the request body.
	 */
	protected static function PUT_realName(): void {
		$realName = trim(self::ReadRequestText());
		if (!$realName)
			self::NeedMoreInfo('Real name must be included in the request body.');
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		self::Success($player->SetRealName($db, $realName));
	}

	/**
	 * Gets the current player's profile information, including profiles with avatars.
	 */
	protected static function GET_avatars(): void {
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		require_once CLASS_PATH . 'profile.php';
		self::Success(AvatarProfile::List($db, $player->id));
	}

	/**
	 * Sets the current player's avatar to the specified profile ID, passed as the request body.  Use 0 for the default avatar.
	 */
	protected static function PUT_avatar(): void {
		$profile = +self::ReadRequestText();
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		$player->SetAvatarProfile($db, $profile);
		self::Success();
	}

	/**
	 * Gets the list of links for the current player.
	 */
	protected static function GET_links(): void {
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		require_once CLASS_PATH . 'profile.php';
		self::Success(ProfileSettings::Settings($db, $player));
	}

	/**
	 * Sets the visibility of a link belonging to the current player.
	 * @param array $params Must have link ID for the first element
	 */
	protected static function PATCH_linkVisibility($params): void {
		$id = +array_shift($params);
		if (!$id)
			self::NeedMoreInfo('Link ID must be included in the request URL such as settings/linkVisibility/{linkId}');
		require_once CLASS_PATH . 'visibility.php';
		$visibility = Visibility::from(+self::ReadRequestText());
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		require_once CLASS_PATH . 'profile.php';
		self::Success(ProfileSettings::UpdateVisibility($db, $player, $id, $visibility));
	}

	/**
	 * Adds a new link to the current player’s profile.  The link URL must be included in the request body.
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
			self::DatabaseError($error);
		self::Success(ProfileSettings::AddLink($db, $player, $url));
	}

	/**
	 * Removes a link from the current player’s profile.
	 * @param array $params Must have link ID for the first element, pointing to a link owned by the player and not used for sign-in or email.
	 */
	protected static function DELETE_link($params): void {
		$id = +array_shift($params);
		if (!$id)
			self::NeedMoreInfo('Link ID must be included in the request URL such as settings/link/{linkId}');
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		require_once CLASS_PATH . 'profile.php';
		ProfileSettings::RemoveLink($db, $player, $id);
		self::Success();
	}

	/**
	 * Gets the list of email addresses linked to the current player.
	 */
	protected static function GET_email(): void {
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		require_once CLASS_PATH . 'email.php';
		self::Success(Email::List($db, $player->id));
	}

	/**
	 * Adds a new email address linked to the current player.  The email address
	 * must be included in the request body and valid to add.
	 */
	protected static function POST_addEmail(): void {
		$address = trim(self::ReadRequestText());
		if (!$address)
			self::NeedMoreInfo('Email address must be included in the request body.');
		require_once CLASS_PATH . 'email.php';
		if (!Email::Valid($address))
			self::DatabaseError('Does not look like an email address');
		$db = self::RequireLatestDatabase();
		$linked = Email::UsedBy($db, $address);
		$player = self::RequirePlayer($db);
		if ($linked)
			if ($player->id == $linked)
				self::DatabaseError('Email address is already linked to you');
			else
				self::DatabaseError('Email address already linked to another player');
		self::Success($player->AddEmail($db, $address));
	}

	/**
	 * Sets the primary email address for the current player to the value
	 * specified in the request body.  The email address must already be
	 * linked to the player.
	 */
	protected static function PUT_primaryEmail(): void {
		$address = trim(self::ReadRequestText());
		if (!$address)
			self::NeedMoreInfo('Email address must be included in the request body.');
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		require_once CLASS_PATH . 'email.php';
		self::Success(Email::SetPrimary($db, $player->id, $address));
	}

	/**
	 * Removes an email address from the current player's account.  The email
	 * address must be included in the request body and linked to the player.
	 */
	protected static function POST_removeEmail(): void {
		$address = trim(self::ReadRequestText());
		if (!$address)
			self::NeedMoreInfo('Email address must be included in the request body.');
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		require_once CLASS_PATH . 'email.php';
		self::Success(Email::Remove($db, $player->id, $address));
	}

	/**
	 * Get the list of sign-in accounts for the current player.
	 */
	protected static function GET_accounts(): void {
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		self::Success($player->GetAccounts($db));
	}

	/**
	 * Delete a sign-in account for the current player.
	 * @param array $params Must have site ID for the first element and account ID for the second
	 */
	protected static function DELETE_account($params): void {
		$site = trim(array_shift($params));
		$id = trim(array_shift($params));
		if (!$site || !$id)
			self::NeedMoreInfo('Authentication site ID and account ID must be included in the request URL such as settings/account/{siteId}/{accountId}.');
		$db = self::RequireLatestDatabase();
		$player = self::RequirePlayer($db);
		require_once CLASS_PATH . 'account.php';
		$account = new Account($site, $id, $db);
		if (!$account)
			self::NotFound("Could not find $site account $id.");
		if ($account->player != $player->id)
			self::DatabaseError("Cannot unlink $site account $id because it is linked to a different player.");
		require_once CLASS_PATH . 'profile.php';
		$db->begin_transaction();
		Profile::Delete($db, $account->profile);
		Account::Delete($db, $site, $id);
		$db->commit();
		self::Success();
	}
}
SettingsApi::Respond();
