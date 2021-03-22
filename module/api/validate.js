import ApiBase from "./apiBase.js";

const urlbase = "api/validate/";

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
		return super.GET(urlbase + "username/" + username);
	}

	/**
	 * Validate an email address.
	 * @param {string} address Email address to validate
	 * @return {Promise} Validation result
	 */
	static Email(address) {
		return super.GET(urlbase + "email/" + address);
	}
}
