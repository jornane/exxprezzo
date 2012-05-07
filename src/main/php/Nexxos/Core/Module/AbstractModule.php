<?php
namespace Nexxos\Core\Module {
	abstract class AbstractModule implements \Nexxos\Core\Runnable {
		protected $isMain;
		private $urlManager;
		
		public static function getInstanceFor($hostname, $internalPath) {
			$path = trim($internalPath, '/');
			$dbh = \Nexxos\Core\Core::getDatabaseConnection();
			$stmt = $dbh->prepare('SELECT `hostGroupId`, `type` FROM `hostGroup` where `hostName` = ? OR `hostName` = ""');
			if ($stmt->execute(array($hostname)) && $hostGroupEntry = $stmt->fetch()) {
				if ($hostGroupEntry['type'] == 'redirect') {/* fixme */}
				$hostGroup = $hostGroupEntry['hostGroupId'];
			}
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
		
		public function getFunctionPath() {
			
		}
	}
}