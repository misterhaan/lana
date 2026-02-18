import ApiBase from "./apiBase.js";

const urlBase = "api/settings/";

/**
 * Javascript client for the settings API
 */
export default class SettingsApi extends ApiBase {
	/**
	 * Get the current player's avatar profile information.
	 * @returns {Promise} Object with avatar profile information
	 */
	static ListAvatars() {
		return super.GET(urlBase + "avatars");
	}

	/**
	 * Sets the current player's avatar to the specified profile ID.  Use 0 for the default avatar.
	 * @param {number} profileId ID of the profile to use as the player's avatar, or 0 to use the default avatar
	 * @returns {Promise} No data
	 */
	static SetAvatar(profileId) {
		return super.PUT(urlBase + "avatar", `${profileId}`);
	}

	/**
	 * Sets the current player's username to the specified value.  Returns validation status, and only a valid username will be set.
	 * @param {string} username New username to set
	 * @returns {Promise} Validation result object
	 */
	static SetUsername(username) {
		return super.PUT(urlBase + "username", username);
	}

	/**
	 * Sets the current player's real name to the specified value.
	 * @param {string} realName New real name to set
	 * @returns {Promise} No data
	 */
	static SetRealName(realName) {
		return super.PUT(urlBase + "realName", realName);
	}

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
