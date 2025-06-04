import ApiBase from "./apiBase.js";

const urlbase = "api/setup/";

/**
 * Javascript client for the setup API
 */
export default class SetupApi extends ApiBase {
	/**
	 * Get the current setup level.
	 * @return {Promise} The current setup level along with data for taking the next step
	 */
	static Level() {
		return super.GET(urlbase + "level");
	}

	/**
	 * Configure the database and external API connections.
	 * @param {string} host - Database hostname
	 * @param {string} name - Database name
	 * @param {string} user - Database username
	 * @param {string} pass - Database password
	 * @param {string} twitchId - Client ID for Twitch API
	 * @param {string} twitchSecret - Client secret for Twitch API
	 * @param {string} googleId - Client ID for Google API
	 * @param {string} googleSecret - Client secret for Google API
	 */
	static ConfigureConnections(host, name, user, pass, twitchId, twitchSecret, googleId, googleSecret) {
		return super.POST(urlbase + "configureConnections", {
			host: host,
			name: name,
			user: user,
			pass: pass,
			twitchId: twitchId,
			twitchSecret: twitchSecret,
			googleId: googleId,
			googleSecret: googleSecret
		});
	}

	/**
	 * Install a new database.
	 */
	static InstallDatabase() {
		return super.POST(urlbase + "installDatabase");
	}

	/**
	 * Upgrade the database after an update that requires it.
	 */
	static UpgradeDatabase() {
		return super.POST(urlbase + "upgradeDatabase");
	}
}
