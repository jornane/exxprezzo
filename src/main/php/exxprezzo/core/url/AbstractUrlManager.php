<?php namespace exxprezzo\core\url;

use \exxprezzo\core\Core;

abstract class AbstractUrlManager {
	
	protected $internalPath;
	
	public function __construct(&$server, &$get, &$post, &$cookie, &$env) {
		if (!is_array($server)) $server = $_SERVER;
		if (!is_array($get)) $get = $_GET;
		if (!is_array($post)) $post = $_POST;
		if (!is_array($cookie)) $cookie = $_COOKIE;
		if (!is_array($env)) $env = $_ENV;
		if (!isset($_SERVER['QUERY_STRING'])) $_SERVER['QUERY_STRING'] = '';
		if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') $_SERVER['HTTPS'] = NULL;
		
		$this->server = $server;
		$this->get    = $get;
		$this->post   = $post;
		$this->cookie = $cookie;
		$this->env    = $env;
	}
	
	/**
	 * Splits a url like /foo/bar/ into an array consisting of
	 * ""
	 * "foo"
	 * "foo/bar"
	 *
	 * @return array
	 */
	public static final function pathOptions($path, $separator = '/') {
		$result = array();
		$thispath = '';
		if (substr($path, 0-strlen($separator)) !== $separator) $path .= $separator;
		if (substr($path, 0, strlen($separator)) === $separator) $path = substr($path, 1);
		$path = explode($separator, preg_replace('/([\\?\\&][^\\?^\\&]+)$/', '', $path));
		foreach($path as $piece) {
			$result[] = $thispath;
			if (strlen($thispath) > 0) $thispath .= $separator;
			$thispath .= $piece;
		}
		return $result;
	}
	
	public static final function abs2relative($from, $to) {
		$from = explode('/', $from);
		$to = explode('/', $to);

		$filename = array_pop($to);
		array_pop($from); // Remove filename from $from
		array_shift($from); // Remove heading slash

		while(!empty($from) && !empty($to) && $from[0] == $to[0]) {
			array_shift($from);
			array_shift($to);
		}
		$result = str_repeat('../', count($from));
		if ($to) $result .= implode('/', $to) . '/';
		$result .= $filename;
		return $result ? $result : './';
	}
	
	public final function getHostGroup() {
		return HostGroup::getInstance($this->server['HTTP_HOST']);
	}
	
	/**
	 * Return the part of the URL that reflects which content was requested by the user.
	 * This is the full URL minus the path to the exxprezzo index.php file
	 */
	public function getPath() {
		return $this->internalPath;
	}
	
	public function registerMainModule() {
		$this->server['MODULE_PATH'] = Core::getMainModule()->getModulePath();
		$this->server['FUNCTION_PATH'] = Core::getMainModule()->getMainFunctionPath();
	}
		
	public abstract function mkurl($hostGroup, $path, $get=array(), $fullUrl=false, $noGetForce=true);
	
	public abstract function serverpath($path);
	
	public final function getUserHostName() {
		return $this->server['HTTP_HOST'];
	}
	public final function getUserIpAddr() {
		return $this->server['REMOTE_ADDR'];
	}
	public final function getUserPort() {
		return $this->server['REMOTE_PORT'];
	}
	public final function getUserAgent() {
		return $this->server['HTTP_USER_AGENT'];
	}
	public final function doNotTrack() {
		return isset($this->server['HTTP_DNT']) && !!$this->server['HTTP_DNT'];
	}
	public final function getPreferredLanguage() {
		return $this->server['HTTP_ACCEPT_LANGUAGE'];
	}
	
	public final function getServerSoftware() {
		return $this->server['SERVER_SOFTWARE'];
	}
	public final function getServerName() {
		return $this->server['SERVER_NAME'];
	}
	public final function getServerIpAddr() {
		return $this->server['SERVER_ADDR'];
	}
	public final function getServerPort() {
		return $this->server['SERVER_PORT'];
	}
	public final function isSSL() {
		return isset($this->server['https']) && $this->server['https'] != 'off' && $this->server['https'];
	}
	
	public final function getRequestMethod() {
		return $this->server['REQUEST_METHOD'];
	}
	public final function getQueryString() {
		return $this->server['QUERY_STRING'];
	}
	public final function getRequestUrl() {
		return $this->server['REQUEST_URI'];
	}
	public final function getRequestTime() {
		return $this->server['REQUEST_TIME'];
	}
	
	public final function getBaseUrl() {
		return $this->server['BASE_URL'];
	}
	public final function getInternalPath() {
		return $this->server['INTERNAL_PATH'];
	}
	
}
