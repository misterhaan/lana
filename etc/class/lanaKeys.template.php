<?php

/**
 * Access keys template for LAN Ahead.  Copy one directory level up from
 * document root, name .lanaKeys.php, and fill in with access values.  This
 * obviously needs to be left blank on GitHub to avoid sharing secrets.
 */
class KeysDB {
	/**
	 * Hostname for database (often this is localhost)
	 * @var string
	 */
	const Host = '';

	/**
	 * Name of database
	 * @var string
	 */
	const Name = '';

	/**
	 * Username with access to the database
	 * @var string
	 */
	const User = '';

	/**
	 * Password for user with access to the database
	 * @var string
	 */
	const Pass = '';
}

/**
 * Client ID and secret for connecting to Twitch.  These can be found at
 * https://dev.twitch.tv/console/apps/ after registering a new application, or
 * choosing to manage an existing application.
 */
class KeysTwitch {
	/**
	 * Client ID for Twitch application
	 * @var string
	 */
	const ClientId = '';

	/**
	 * Client secret for Twitch application (must create a new secret because it
	 * will not display)
	 * @var string
	 */
	const ClientSecret = '';
}

/**
 * Client ID and secret for connecting to Google.  These can be found at
 * https://console.cloud.google.com/auth/clients after creating a new client, or
 * choosing an existing client.
 */
class KeysGoogle {
	/**
	 * Client ID for Google application
	 * @var string
	 */
	const ClientId = '';

	/**
	 * Client secret for Google application (must create a new secret because it
	 * will not display)
	 * @var string
	 */
	const ClientSecret = '';
}
