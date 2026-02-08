import ApiBase from "./apiBase.js";

const urlBase = "api/validate/";

/**
 * Javascript client for the validate API
 */
export default class ValidateApi extends ApiBase {
	/**
	 * Validate a username.
	 * @param {string} username Username to validate
	 * @return {Promise} Validation result
	 */
	static Username(username) {
		return super.POST(urlBase + "username", username);
	}

	/**
	 * Validate an email address.
	 * @param {string} address Email address to validate
	 * @return {Promise} Validation result
	 */
	static Email(address) {
		return super.POST(urlBase + "email", address);
	}

	/**
	 * Validate a link URL the player intends to add to their profile.
	 * @param {string} url URL of link to add
	 * @returns {Promise} Validation result
	 */
	static AddLink(url) {
		return super.POST(urlBase + "addLink", url);
	}
}
