import ApiBase from "./apiBase.js";

const urlbase = "api/auth/";

/**
 * Javascript client for the auth API
 */
export default class AuthApi extends ApiBase {
	/**
	 * Get the list of authentication sites.
	 * @return {Promise} Array of authentication site names
	 */
	static List() {
		return super.GET(urlbase + "list");
	}

	/**
	 * Get the currently signed-in player, if any.
	 * @return {Promise} Object for currently signed-in player, or
	 */
	static Player() {
		return super.GET(urlbase + "player");
	}

	/**
	 * Get the sign-in URL for an authentication site.
	 * @param {string} siteId ID of authentication site (see AuthApi.List() for options)
	 * @param {string} returnHash Location hash LANA should return to after sign-in
	 * @param {bool} remember True if LANA should remember this login in a cookie
	 * @return {Promise} External sign-in URL
	 **/
	static GetSignInUrl(siteId, returnHash, remember) {
		let url = urlbase + "url/" + siteId;
		// handle boolean parameter manually so it doesn't go through as =true or =false
		if(returnHash && returnHash != "#") {
			url += "?returnHash=" + encodeURIComponent(returnHash);
			if(remember)
				url += "&remember"
		} else if(remember)
			url += "?remember"
		return super.GET(url);
	}

	/**
	 * Handle a response from an authentication site.  Could result in not
	 * authenticated, authenticated but not matched to LANA player, or
	 * authenticated and matched to LANA player.
	 * @param {string} siteId ID of authentication site (see AuthApi.List() for options)
	 * @param {string} queryString Data to include with the signin request, in query string format
	 * @return {Promise} result of the authentication attempt
	 */
	static SignIn(siteId, queryString) {
		return super.POST(urlbase + "signin/" + siteId, queryString);
	}

	/**
	 * Process new signin and create a new LANA player account.
	 * @param {string} siteId ID of authentication site (see AuthApi.List() for options)
	 * @param {string} username Player username
	 * @param {string} realName Player real name
	 * @param {string} email Player email address
	 * @param {string} Player avatar source (account, gravatar, or default)
	 */
	static Register(siteId, username, realName, email, avatar) {
		return super.POST(urlbase + "register/" + siteId, {
			username: username,
			realName: realName,
			email: email,
			avatar: avatar
		});
	}

	/**
	 * Sign out the current player.
	 */
	static SignOut() {
		return super.POST(urlbase + "signout");
	}
}
