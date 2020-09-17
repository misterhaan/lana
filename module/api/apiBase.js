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
		return this.Ajax("GET", url, data)
	}

	/**
	 * Perform an HTTP POST request to ask the server to do something.
	 * @param {string} url - URL to request
	 * @param {Object.<string, any>} [data] - Named properties to send as POST data
	 */
	static POST(url, data) {
		return this.Ajax("POST", url, data);
	}

	/**
	 * Perform an HTTP PATCH request to update a record on the server.
	 * @param {string} url - URL to request
	 * @param {Object.<string, any>} [data] - Named properties to send as data in the request body
	 */
	static PATCH(url, data) {
		return this.Ajax("PATCH", url, data);
	}

	/**
	 * Perform an HTTP PUT request to add or replace a record on the server.
	 * @param {string} url - URL to request
	 * @param {Object.<string, any>} [data] - Named properties to send as data in the request body
	 */
	static PUT(url, data) {
		return this.Ajax("PUT", url, data);
	}

	/**
	 * Perform an HTTP request with some error handling.
	 * @private
	 * @param {string} method - HTTP request method for the request
	 * @param {string} url - URL to request
	 * @param {Object.<string, any>} [data] - Data to send with the request
	 */
	static Ajax(method, url, data = {}) {
		return $.ajax({
			method: method,
			url: url,
			data: data,
			dataType: "json"
		}).then(
			result => result,
			request => {
				if(request.status == 503 && request.statusText == "Setup Needed" && !location.href.includes("setup.html"))
					location = "setup.html";
				const error = new Error(
					request.getResponseHeader("Content-Type").split(";")[0] == "text/plain"
						? request.responseText
						: `${request.status} ${request.statusText} from ${url}`
				);
				error.url = url;
				error.status = request.status;
				error.statusText = request.statusText;
				throw error;
			});
	}
}
