<?php
/**
 * Collection of URL-related functions.
 */
class Url {
	/**
	 * A more useful document root (that's correct when there’s an alias).
	 * @return string Full OS path to the webserver root path
	 **/
	public static function DocRoot() {
		if(!self::$docRoot)
			// CONTEXT_DOCUMENT_ROOT is set when an alias or similar is used, which makes
			// DOCUMENT_ROOT incorrect for this purpose.  assume the presence of an alias
			// means we're one level deep.
			self::$docRoot = isset($_SERVER['CONTEXT_PREFIX']) && isset($_SERVER['CONTEXT_DOCUMENT_ROOT']) && $_SERVER['CONTEXT_PREFIX']
				? dirname($_SERVER['CONTEXT_DOCUMENT_ROOT'])
				: $_SERVER['DOCUMENT_ROOT'];
		return self::$docRoot;
	}
	private static $docRoot = false;

	/**
	 * Expand an application URL into an absolute URL.
	 * @param string $rootUrl Application URL
	 * @return string Absolute URL
	 **/
	public static function FullUrl($rootUrl) {
		if($rootUrl[0] != '/')
			$rootUrl = '/' . $rootUrl;
		return self::Scheme() . '://' . self::Host() . self::InstallPath() . $rootUrl;
	}

	/**
	 * Get the application path on the webserver for absolute URLs.  Starts with
	 * a slash but does not end with one.
	 * @return string Application path on the webserver
	 **/
	public static function InstallPath() {
		if(self::$path === false) {  // need to check against false exactly because it can be an empty string
			self::$path = dirname(dirname(substr(__DIR__, strlen(self::DocRoot()))));
			if(self::$path == '/')
				self::$path = '';
		}
		return self::$path;
	}
	private static $path = false;

	/**
	 * Get the URL scheme for this website (usually https or http).
	 * @return string URL scheme for this website
	 **/
	private static function Scheme() {
		if(!self::$scheme)
			self::$scheme = isset($_SERVER['REQUEST_SCHEME'])
				? $_SERVER['REQUEST_SCHEME']
				: isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on'
					? 'https'
					: 'http';
		return self::$scheme;
	}
	private static $scheme = false;

	/**
	 * Get the hostname (and port, if needed) for accessing this website.
	 * @return string Hostname for this website
	 **/
	private static function Host() {
		if(!self::$host) {
			self::$host = $_SERVER['SERVER_NAME'];
			$port = $_SERVER['SERVER_PORT'];
			// don't include standard ports.  assumes we won't have swapped the standard ports for http and https
			if($port != 80 && $port != 443)
				self::$host .= ':' . $port;
		}
		return self::$host;
	}
	private static $host = false;
}
