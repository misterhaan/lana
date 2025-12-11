import ApiBase from "./apiBase.js";

const urlbase = "api/player/";

/**
 * Javascript client for the player API
 */
export default class PlayerApi extends ApiBase {
	/**
	 * Get the list of registered players.
	 * @return {Promise} Array of registered players
	 */
	static List() {
		return super.GET(`${urlbase}list`);
	}

	/**
	 * Get the profile for a player.
	 * @param {string} name Player username to look up
	 * @return {Promise} Player profile object
	 */
	static Profile(name) {
		return super.GET(`${urlbase}profile/${name}`);
	}
}
