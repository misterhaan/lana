/**
 * Server API base class
 */
export default class ApiBase {
	/**
	 * Perform an HTTP GET request to retrieve information from the server.
	 * @param {string} url - URL to request
	 * @param {Object.<string, any>} [data] - Request parameters to include in the query string
	 */
	static GET(url, data) {
		return ajax("GET", url, data)
	}

	/**
	 * Perform an HTTP POST request to ask the server to do something.
	 * @param {string} url - URL to request
	 * @param {Object.<string, any>} [data] - Named properties to send as POST data
	 */
	static POST(url, data) {
		return ajax("POST", url, data);
	}

	/**
	 * Perform an HTTP PATCH request to update a record on the server.
	 * @param {string} url - URL to request
	 * @param {Object.<string, any>} [data] - Named properties to send as data in the request body
	 */
	static PATCH(url, data) {
		return ajax("PATCH", url, data);
	}

	/**
	 * Perform an HTTP PUT request to add or replace a record on the server.
	 * @param {string} url - URL to request
	 * @param {Object.<string, any>} [data] - Named properties to send as data in the request body
	 */
	static PUT(url, data) {
		return ajax("PUT", url, data);
	}

	/** Perform an HTTP DELETE request to remove a record from the server.
	 * @param {string} url - URL to request
	 */
	static DELETE(url) {
		return ajax("DELETE", url);
	}
}
/**
 * Perform an HTTP request with some error handling.
 * @private
 * @param {string} method - HTTP request method for the request
 * @param {string} url - URL to request
 * @param {Object.<string, any>} [data] - Data to send with the request
 */
async function ajax(method, url, data) {
	const init = { method: method };
	if(data)
		if(typeof data == "string" || data instanceof FormData || data instanceof URLSearchParams)
			init.body = data;
		else
			init.body = new URLSearchParams(data);
	const response = await fetch(url, init);
	if(!response.ok)
		return await handleError(response);
	return await response.json();
}

async function handleError(response) {
	if(response.status == 503 && response.statusText == "Setup Needed" && !location.href.includes("setup.html"))
		location = "setup.html";
	if(response.status == 401 && document.SignOut)
		document.SignOut();
	return await throwAsync(response);
}

async function throwAsync(response) {
	const responseText = await response.text();
	throw new Error(`${response.status} ${responseText || response.statusText} from ${response.url}`);
}
