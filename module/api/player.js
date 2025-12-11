import ApiBase from "./apiBase.js";

const urlbase = "api/player/";

/**
 * Javascript client for the player API
 */
export default class PlayerApi extends ApiBase {
	static List() {
		return super.GET(`${urlbase}list`);
	}
}
