import ApiBase from "./apiBase.js";

const urlBase = "api/settings/";

/**
 * Javascript client for the settings API
 */
export default class SettingsApi extends ApiBase {
	/**
	 * Get a list of links for the current player.  Includes visibility setting.
	 * @returns {Promise} Object with array of links
	 */
	static ListLinks() {
		return super.GET(urlBase + "links");
	}

	/**
	 * Set visibility of a link for the current player.
	 * @param {number} id Link ID to set visibility
	 * @param {number} visibility Visibility level to set
	 * @returns {Promise} No data
	 */
	static LinkVisibility(id, visibility) {
		return super.PATCH(urlBase + "linkVisibility/" + id, `${visibility}`);
	}

	/**
	 * Add a link to the current player’s profile.
	 * @param {string} url URL of link to add
	 * @returns {Promise} Added link object
	 */
	static AddLink(url) {
		return super.POST(urlBase + "addLink", url);
	}

	/**
	 * Remove a link from the current player's profile.
	 * @param {number} id ID of link to remove
	 * @returns {Promise} No data
	 */
	static RemoveLink(id) {
		return super.DELETE(urlBase + "link/" + id);
	}

	/**
	 * Get the list of sign-in accounts linked to the current player.
	 * @return {Promise} Array of sign-in accounts
	 */
	static ListAccounts() {
		return super.GET(urlBase + "accounts");
	}

	/**
	 * Unlink a sign-in account from the current player.
	 * @param {string} site ID of external authentication site that owns the account
	 * @param {string} id Identifier of the account on the external authentication site
	 * @return {Promise} No data
	 */
	static UnlinkAccount(site, id) {
		return super.DELETE(`${urlBase}account/${site}/${id}`);
	}
}
