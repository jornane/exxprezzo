<?php namespace exxprezzo\core\url;

use \exxprezzo\core\Core;

abstract class AbstractUrlManager {
	
	protected $internalPath;
	protected $forcedGetVars = array(), $forcedPostVars = array();
	
	protected $server, $get, $post, $cookie, $env;
	
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
	
	public static function resolvePath($absolute) {
		assert('is_string($absolute);');
		assert('$absolute{0}=="/"');
		
		$result = array('');
		$source = explode('/', $absolute);
		foreach($source as $segment) {
			if ($segment == '..')
				array_pop($result);
			elseif ($segment && $segment != '.')
				array_push($result, $segment);
		}
		if (!end($source) || end($source) == '.')
			array_push($result, '');
		return implode('/', $result);
	}
	
	/**
	 * Return the part of the URL that reflects which content was requested by the user.
	 * This is the full URL minus the path to the exxprezzo index.php file
	 */
	public function getPath() {
		return $this->internalPath;
	}
	
	public function registerMainModule() {
		if (static::resolvePath($this->getInternalPath()) != $this->getInternalPath())
			$this->redirect($this->getHostGroup(), static::resolvePath($this->getInternalPath()));
		$this->server['MODULE_PATH'] = Core::getMainModule()->getModulePath();
		$this->server['FUNCTION_PATH'] = Core::getMainModule()->getMainFunctionPath();
	}
		
	public abstract function mkurl($hostGroup, $path, $get=array(), $fullUrl=false, $noGetForce=true);
	
	public function redirect($hostGroup, $path, $get=array(), $noGetForce=true) {
		header('Location: '.$this->mkurl($hostGroup, $path, $get, true, $noGetForce));
	}
	
	public abstract function serverpath($path);
	
	public final function forceGetVariable($key, $value) {
		$this->forcedGetVars[$key] = $value;
	}
	public final function forcePostVariable($key, $value) {
		$this->forcedPostVars[$key] = $value;
	}
	public final function forceVariable($key, $value) {
		$this->forceGetVariable($key, $value);
		$this->forcePostVariable($key, $value);
	}
	
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
	public final function getReferer() {
		return $this->server['HTTP_REFERER'];
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
	
	public final function isPost() {
		return $this->server['REQUEST_METHOD'] == 'POST';
	}
	public final function isHead() {
		return $this->server['REQUEST_METHOD'] == 'HEAD';
	}
	public final function isGet() {
		return $this->server['REQUEST_METHOD'] == 'GET' || $this->server['REQUEST_METHOD'] == 'HEAD';
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
	public final function getModulePath() {
		return $this->server['MODULE_PATH'];
	}
	public final function getMainFunctionPath() {
		return $this->server['FUNCTION_PATH'];
	}
	
	public final function getPost($key) {
		if (isset($this->post[$key]))
			return $this->post[$key];
	}
	public final function getGet($key) {
		if (isset($this->get[$key]))
			return $this->get[$key];
	}
	public final function getCookie($key) {
		if (isset($this->cookie[$key]))
			return $this->cookie[$key];
	}
	public final function isInPost($key) {
		return isset($this->post[$key]);
	}
	public final function isInGet($key) {
		return isset($this->get[$key]);
	}
	public final function isInCookie($key) {
		return isset($this->cookie[$key]);
	}
	public final function getRawPost() {
		return $this->post;
	}
	public final function getRawGet() {
		return $this->get;
	}
	public final function getRawCookie() {
		return $this->cookie;
	}
	
}
