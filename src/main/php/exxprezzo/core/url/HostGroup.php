<?php namespace exxprezzo\core\url;

use \exxprezzo\core\Core;

final class HostGroup {
	
	private static $instances = array();
	
	static function getInstance($hostname, $prohibitRedirect = false) {
		$dbh = Core::getDatabaseConnection();
		$stmt = $dbh->prepare('SELECT `hostGroupId`, `type` FROM `hostGroup` WHERE ? LIKE `hostName` OR `hostName` = "" ORDER BY `hostname`!="", type`="primary"');
		if ($stmt->execute(array($hostname)) && $hostGroupEntry = $stmt->fetch()) {
			if (!$prohibitRedirect && $hostGroupEntry['type'] == 'redirect') {
				throw new RedirectException(array('host' => $newHost));
			}
			if (isset(self::$instances[$hostGroupEntry['hostGroupId']]))
				return self::$instances[$hostGroupEntry['hostGroupId']];
			return self::$instances[$hostGroupEntry['hostGroupId']]
				= new HostGroup($hostGroupEntry['hostGroupId']);
		}
	}
	
	private $hostGroupId;
	private $primaryHostName;
	private $slaveHostNames = array();
	private $redirectHostNames = array();
	
	public function __construct($hostGroupId) {
		$this->hostGroupId = $hostGroupId;
	}
	
	private function lazyLoad() {
		$stmt = $dbh->prepare('SELECT `hostName`, `type` FROM `hostGroup` WHERE `hostGroupId` = ?');
		if ($stmt->execute(array((int)$this->hostGroupId))) while ($hostGroupEntry = $stmt->fetch()) {
			switch($hostGroupEntry['type']) {
				case 'primary':$this->primaryHostName=$hostGroupEntry['type'];break;
				case 'slave':$this->slaveHostNames[]=$hostGroupEntry['type'];break;
				case 'redirect':$this->redirectHostNames[]=$hostGroupEntry['type'];break;
			}
		}
	}
	
	public function getPrimary() {
		if (is_null($this->primaryHostName)) $this->lazyLoad();
		return $this->primaryHostName;
	}
	
	public function getSlaves() {
		if (is_null($this->primaryHostName)) $this->lazyLoad();
		return $this->slaveHostNames;
	}
	
	public function getRedirects() {
		if (is_null($this->primaryHostName)) $this->lazyLoad();
		return $this->redirectHostNames;
	}
	
	public function setPrimary($hostName) {
		$this->primaryHostName = $this->slaveHostNames = $this->redirectHostNames = NULL;
		$stmt = $dbh->prepare('REPLACE `hostGroup` SET `hostGroupId`=:hostGroupId, `hostName`=:hostName, `type`="primary"');
		$stmt->execute(array(
				'hostGroupId' => $this->hostGroupId,
				'hostName' => $hostName,
			));
		$stmt = $dbh->prepare('UPDATE `hostGroup` SET `type`="slave", WHERE `hostGroupId`=:hostGroupId AND `hostName`!=:hostName AND `type`="primary');
		$stmt->execute(array(
				'hostGroupId' => $this->hostGroupId,
				'hostName' => $hostName,
			));
	}
	
	public function putSlave($hostName) {
		$this->primaryHostName = $this->slaveHostNames = $this->redirectHostNames = NULL;
		$stmt = $dbh->prepare('REPLACE `hostGroup` SET `hostGroupId`=:hostGroupId, `hostName`=:hostName, `type`="slave"');
		$stmt->execute(array(
				'hostGroupId' => $this->hostGroupId,
				'hostName' => $hostName,
		));
	}
	
	public function putRedirect($hostName) {
		$this->primaryHostName = $this->slaveHostNames = $this->redirectHostNames = NULL;
		$stmt = $dbh->prepare('REPLACE `hostGroup` SET `hostGroupId`=:hostGroupId, `hostName`=:hostName, `type`="redirect"');
		$stmt->execute(array(
				'hostGroupId' => $this->hostGroupId,
				'hostName' => $hostName,
		));
	}
	
	public function removeSlave($hostName) {
		$this->primaryHostName = $this->slaveHostNames = $this->redirectHostNames = NULL;
		$stmt = $dbh->prepare('DELETE FROM `hostGroup` WHERE `hostGroupId`=:hostGroupId AND `hostName`=:hostName AND `type`="slave"');
		$stmt->execute(array(
				'hostGroupId' => $this->hostGroupId,
				'hostName' => $hostName,
		));
	}
	
	public function removeRedirect($hostName) {
		$this->primaryHostName = $this->slaveHostNames = $this->redirectHostNames = NULL;
		$stmt = $dbh->prepare('DELETE FROM `hostGroup` WHERE `hostGroupId`=:hostGroupId AND `hostName`=:hostName AND `type`="redirect"');
		$stmt->execute(array(
				'hostGroupId' => $this->hostGroupId,
				'hostName' => $hostName,
		));
	}
	
	public function destroy() {
		$stmt = $dbh->prepare('DELETE FROM `hostGroup` WHERE `hostGroupId`=:hostGroupId');
		$stmt->execute(array(
				'hostGroupId' => $this->hostGroupId,
		));
	}
	
}
