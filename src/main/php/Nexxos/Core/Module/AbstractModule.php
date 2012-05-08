<?php
namespace Nexxos\Core\Module {
	abstract class AbstractModule implements \Nexxos\Core\Runnable {
		protected $isMain;
		private $urlManager;
		
		public static function getInstanceFor($hostGroup, $internalPath) {
			$path = trim($internalPath, '/');
			$dbh = \Nexxos\Core\Core::getDatabaseConnection();
			$options = \Nexxos\Core\URL\AbstractUrlManager::pathOptions($path);
			$stmt = $dbh->prepare('SELECT `moduleInstanceId`, `module`, `root` FROM `moduleInstance` where `root` IN('.str_repeat('\'?\',', sizeof($options)-1).'\'?\')');
			if ($stmt->execute($options) && $instanceEntry = $stmt->fetch()) {
				$instanceId = $instanceEntry['moduleInstanceId'];
				$module = $instanceEntry['module'];
				$moduleFQN = '\\Nexxos\\module\\'.strtolower($module).'\\'.$module;
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
			if ($urlManager instanceof \Nexxos\Core\URL\AbstractUrlManager)
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
			for($paths as $path) {
				$vars = extractVars($path);i
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
			const $regex = '/^.*({\\$(?<name>.*)}.*)*$/';
			$mathes = array ()
			preg_match($regex,$path,$mathes);
			$names = $mathes['name'];
			$result = array();
			for($names as $name){
				$result[$name] = NULL;
			}
			return array_keys($result);
		}
	}
}
