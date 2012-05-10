<?php namespace exxprezzo\core\url;

use \exxprezzo\core\Core;

abstract class AbstractUrlManager {
	
	public function __construct(&$server, &$get, &$post, &$cookie, &$env) {
		if (!is_array($server)) $server = $_SERVER;
		if (!is_array($get)) $get = $_GET;
		if (!is_array($post)) $post = $_POST;
		if (!is_array($cookie)) $cookie = $_COOKIE;
		if (!is_array($env)) $env = $_ENV;
		if (!isset($_SERVER['QUERY_STRING'])) $_SERVER['QUERY_STRING'] = '';
		if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') $_SERVER['HTTPS'] = NULL;
		
		$this->server = $_SERVER;
		$this->get    = $_GET;
		$this->post   = $_POST;
		$this->cookie = $_COOKIE;
		$this->env    = $_ENV;
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
		$dbh = Core::getDatabaseConnection();
		$stmt = $dbh->prepare('SELECT `hostGroupId`, `type` FROM `hostGroup` where `hostName` = ? OR `hostName` = ""');
		if ($stmt->execute(array($this->server['HTTP_HOST'])) && $hostGroupEntry = $stmt->fetch()) {
			if ($hostGroupEntry['type'] == 'redirect') {/* fixme */}
			$hostGroup = $hostGroupEntry['hostGroupId'];
		}
	}
	
	public abstract function getPath();
	
	public abstract function mkrawurl($hostGroup, $path, $get=array(), $fullUrl=false, $noGetForce=true);
	
	/**
	 * Return the URL to a specific physical path, relative to the exxprezzo root.
	 * 
	 * @param string $path	Physical path, must not start with a slash
	 * 
	 * @return string	Link to physical file
	 */
	public abstract function serverpath($path);
	
	public final function mkurl($module, $function, $args, $fullUrl=false, $noGetForce=true) {
		$functionPath = $module->getFunctionPath($function, $args);
		return $this->mkrawurl($functionPath->hostGroup, $functionPath->path, $functionPath->get, $fullUrl, $noGetForce);
	}
}
