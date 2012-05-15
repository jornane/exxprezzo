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
		$stmt = $dbh->prepare('SELECT `moduleInstanceId`, `module`, `root` FROM `moduleInstance`
				WHERE `root` IN('.str_repeat('\'?\',', sizeof($options)-1).'\'?\')
				ORDER BY LENGTH(`root`) DESC
				LIMIT 1');
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

	/**
	 * Retrieves a path that will result in a call to the given function
	 * with the given arguments. This function uses a static field named $paths
	 * that subclasses need to fill to provide the paths needed for the function.
	 * The path is an ordinary URL except where the subclass wants an argument
	 * to appear. This can be achieved by placing a place holder {$name} which
	 * will then be replaced by $args['name'] when this function is called. This
	 * function returns the first shortest path created from $paths that first
	 * exactly fits args i.e. for each key in args a place holder appears in the
	 * path and the resulting length is minimal as compared to the possible values
	 * remaining in $paths.
	 * @param string $function the name of the function
	 * @param array $args the arguments to use when constructing the path
	 * @return string A path that maps to a call to $function with $args
	 * as its arguments
	 */
	public static function getFunctionPath($function, $args) {
		if(!isset(static::$paths))
			user_error('This module has no usable paths');
		if(!isset(static::$paths[$function]))
			user_error('This function has no paths in this module');
		$paths = static::$paths[$function];
		$result = NULL;
		foreach($paths as $path) {
			$vars = extractVars($path);
			if($vars === array_keys($args)) {
				$temp = buildFunctionPath($path, $args);
				if($result === NULL || strlen($temp) < strlen($result) )
					$result = $temp;
			}
		}
		if($result === NULL)
			user_error('No suitable path found for function ' . $function . '\nArguments: ' . $args . '\nCandidates considered: ' . $paths);
		return $result;
	}

	/**
	 * Builds a function path from the given path and
	 * arguments. Each occurence of {$key} of args in path will
	 * be replaced by the value of $key in $args
	 * @param string $path the path to build the function path on
	 * @param array $args arguments to use when building the path
	 * @return $path with all occurences of named parameters
	 * replaced by values from $args
	 */
	private static function buildFunctionPath($path, $args) {
		// Build the needle and replace
		$needle = array();
		$replace = array();
		foreach($args as $key => $arg) {
			$needle[] = '{$' . $key . '}';
			$replace[] = $arg;
		}
		return str_replace($needle, $replace, $path);
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
