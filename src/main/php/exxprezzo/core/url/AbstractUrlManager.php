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
		
	public abstract function mkrawurl($hostGroup, $path, $get=array(), $fullUrl=false, $noGetForce=true);
	
	public abstract function serverpath($path);
	
	/**
	 * Return the URL to a specific physical path, relative to the exxprezzo root.
	 * 
	 * @param AbstractModule $module
	 * @param string $function
	 * @param string[] $args
	 * @param string[] $get
	 * @param boolean $fullUrl
	 * @param boolean $noGetForce
	 */
	public final function mkurl($module, $function, $args=array(), $get=array(), $fullUrl=false, $noGetForce=true) {
		$functionPath = $module::getFunctionPath($function, $args);
		return $this->mkrawurl($this->getHostGroup(), $module->getRoot().$functionPath, $get, $fullUrl, $noGetForce);
	}
}
