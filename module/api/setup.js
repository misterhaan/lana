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
	 * Configure the database connection.
	 * @param {string} host - Database hostname
	 * @param {string} name - Database name
	 * @param {string} user - Database username
	 * @param {string} pass - Database password
	 */
	static ConfigureDatabase(host, name, user, pass) {
		return super.POST(urlbase + "configureDatabase", {
			host: host,
			name: name,
			user: user,
			pass: pass
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
