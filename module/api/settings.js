import ApiBase from "./apiBase.js";

const urlbase = "api/settings/";

/**
 * Javascript client for the settings API
 */
export default class SettingsApi extends ApiBase {
	/**
	 * Get the list of sign-in accounts linked to the current player.
	 * @return {Promise} Array of sign-in accounts
	 */
	static ListAccounts() {
		return super.GET(urlbase + "accounts");
	}

	/**
	 * Unlink a sign-in account from the current player.
	 * @param {string} site ID of external authentication site that owns the account
	 * @param {string} id Identifier of the account on the external authentication site
	 * @return {Promise} No data
	 */
	static UnlinkAccount(site, id) {
		return super.DELETE(`${urlbase}account/${site}/${id}`);
	}
}
