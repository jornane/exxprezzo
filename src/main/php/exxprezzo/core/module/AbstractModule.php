<?php namespace exxprezzo\core\module;

use \exxprezzo\core\Core;
use \exxprezzo\core\Runnable;

use \exxprezzo\core\url\AbstractUrlManager;

abstract class AbstractModule implements Runnable {
	protected $isMain;
	private $urlManager;
	
	public static function getInstanceFor($hostGroup, $internalPath) {
		$path = trim($internalPath, '/');
		$dbh = Core::getDatabaseConnection();
		$options = AbstractUrlManager::pathOptions($path);
		$stmt = $dbh->prepare('SELECT `moduleInstanceId`, `module`, `root` FROM `moduleInstance` where `root` IN('.str_repeat('\'?\',', sizeof($options)-1).'\'?\')');
		if ($stmt->execute($options) && $instanceEntry = $stmt->fetch()) {
			$instanceId = $instanceEntry['moduleInstanceId'];
			$module = $instanceEntry['module'];
			$moduleFQN = '\\exxprezzo\\module\\'.strtolower($module).'\\'.$module;
			$result = new $moduleFQN;
			$result->instanceId = $instanceId;
			$result->modulePath = substr($internalPath, strlen($instanceEntry['root'])+1);
			return result;
		}
		// Make me a 404
		user_error('Unable to find suitable module.');
	}
	
	public function setMain($isMain) {
		$this->isMain = $isMain;
	}
	public function isMain() {
		return $this->isMain;
	}
	
	public final function setUrlManager($urlManager) {
		if ($urlManager instanceof AbstractUrlManager)
			$this->urlManager = $urlManager;
		else
			user_error('urlManager is not of kind AbstractUrlManager');
	}
	public final function getUrlManager() {
		return $this->urlManager;
	}
	
	public static function getFunctionPath($function, $args) {
		if(!isset(static::$paths))
			user_error("This module has no usable paths");
		if(!isset($args[$function]))
			user_error("This function has no paths in this module");
		$paths = static::$paths[$function];
		$result = NULL;
		foreach($paths as $path) {
			$vars = extractVars($path);
			if($vars === array_keys($args)) {
			}
		}
	}
	

	/**
	 * Extracts the variables from a given path
	 * 
	 * The path is a path string in which variable
	 * segments are given as {$name}. This will
	 * return a set of all names encountered in
	 * the path
	 * @param string $path The path to extract
	 * the variables from
	 * @return array A set of the variable names contained
	 * in path
	 */
	private static function extractVars($path) {
		$regex = '/^.*({\\$(?<name>.*)}.*)*$/';
		$mathes = array ();
		preg_match($regex,$path,$mathes);
		$names = $mathes['name'];
		$result = array();
		foreach($names as $name){
			$result[$name] = NULL;
		}
		return array_keys($result);
	}
}
