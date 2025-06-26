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
function ajax(method, url, data = {}) {
	return $.ajax({
		method: method,
		url: url,
		data: data,
		dataType: "json"
	}).fail(request => {
		handleError(request, url);
	});
}

function handleError(request, url) {
	if(request.status == 503 && request.statusText == "Setup Needed" && !location.href.includes("setup.html"))
		location = "setup.html";
	if(request.status == 401 && document.SignOut)
		document.SignOut();
	throwAsync(request, url);
}

function throwAsync(request, url) {
	setTimeout(() => {
		throw new Error(`${request.status} ${request.responseText || request.statusText} from ${url}`);
	});
}
