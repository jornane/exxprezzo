<?php namespace exxprezzo\module\sessionmanager;

use \exxprezzo\core\db\SQL;

use \exxprezzo\core\Core;

class Session {

	/** @var \exxprezzo\module\sessionmanager\SessionManager */
	protected $sessionManager;
	/** @var string */
	protected $sid;
	/** @var int */
	protected $moduleId;
	/** @var int */
	protected $defaultLifetime = 3600;
	/** @var SQL */
	protected $db;

	public function __construct($sessionManager, $moduleId, $sid=NULL) {
		assert('$sessionManager instanceof \exxprezzo\module\sessionmanager\SessionManager');
		assert('is_numeric($moduleId)');
		$this->sessionManager = $sessionManager;
		$this->moduleId = $moduleId;
		$this->sid = $sid;
		$this->db = $sessionManager->db;
	}

	public function __set($key, $value) {
		if (is_null($this->sid))
			$this->sid = $this->randomString(64);
		$this->db->replace('var', array(
				'session' => $this->sid,
				'moduleInstance' => $this->moduleId,
				'key' => $key,
				'value' => serialize($value),
				'touched' => time(),
			));
		$this->updateCookie();
	}
	public function __get($key) {
		if (is_null($this->sid))
			return NULL;
		$result = $this->db->query('SELECT `value` FROM `var`
				WHERE `key` = $key
				AND `session` = $session
				AND `moduleInstance` = $moduleInstance
				AND `touched`+`lifetime` > $now', array(
						'key' => $key,
						'session' => $this->sid,
						'moduleInstance' => $this->moduleId,
						'now' => time(),
			));
		return isset($result[0]['value']) ? unserialize($result[0]['value']) : NULL;
	}
	public function __isset($key) {
		if (is_null($this->sid))
			return false;
		$result = $this->db->execute('SELECT `value` FROM `var`
				WHERE `key` = $key
				AND `session` = $session
				AND `moduleInstance` = $moduleInstance
				AND `touched`+`lifetime` < $now', array(
						'key' => $key,
						'session' => $this->sid,
						'moduleInstance' => $this->moduleId,
						'now' => time(),
			));
		return (bool)$this->db->numrows();
	}
	public function __unset($key) {
		if (is_null($this->sid))
			return;
		$this->db->delete('var', array(
				'key' => $key,
				'session' => $this->sid,
				'moduleInstance' => $this->moduleId,
			));
		$this->updateCookie();
	}

	public function setLifetime($key, $lifetime) {
		if (is_null($this->sid))
			return;
		$this->db->replace('var', array(
				'session' => $this->sid,
				'moduleInstance' => $this->moduleId,
				'key' => $key,
				'lifetime' => (int)$lifetime,
				'touched' => time(),
		));
		$this->updateCookie();
	}

	protected static function randomString($length) {
		mt_srand();
		$str = '';
		for($i=0;$i<((int)($length*0.75));$i++)
			$str .= chr(mt_rand(0,255));
		return base64_encode($str);
	}

	protected function updateCookie() {
		if (is_null($this->sid))
			return;
		$result = $this->db->query('SELECT MAX(`touched`+`lifetime`) `max` FROM `var`
				WHERE `session` = $session
				AND `moduleInstance` = $moduleInstance', array(
						'session' => $this->sid,
						'moduleInstance' => $this->moduleId,
			));
		if (isset($result[0]['max'])) {
			$expire = isset($result[0]['max']) ? $result[0]['max'] : 1;
		} else {
			$expire = 1;
			$this->sid = NULL;
		}
		assert('$this->sessionManager->cookie_name');
		setcookie(
				$this->sessionManager->cookie_name,
				$this->sid,
				$expire,
				false,
				'',
				false,
				true
			);
		if (Core::getUrlManager()->isInCookie($this->sessionManager->cookie_name))
			Core::getUrlManager()->forcePostVariable($this->sessionManager->cookie_name, $this->sid);
		else
			Core::getUrlManager()->forceVariable($this->sessionManager->cookie_name, $this->sid);
	}
}
