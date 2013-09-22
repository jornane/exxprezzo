<?php namespace exxprezzo\module\databasepasswd;

use \exxprezzo\core\module\AbstractModule;

use \exxprezzo\module\passwd\User;

class DatabaseUser implements User {

	/** Login name of the user */
	protected $username = NULL;
	/** Database ID of the user */
	protected $id = 0;
	/**
	 * Custom fields for this user
	 * Two dimensional array, first key is the module,
	 * second key is the variable name
	 */
	protected $userField = array();
	/** Non-committed changes in $userField */
	protected $changes = array();
	/** Non-committed removes in $userField */
	protected $removes = array();

	/** @var \exxprezzo\core\db\SQL */
	protected $db;
	/** @var DatabasePasswd */
	protected $passwd;

	public function __construct($dbPasswd, $username, $id) {
		assert('$dbPasswd instanceof \exxprezzo\module\databasepasswd\DatabasePasswd');
		assert('!is_null($username) || !is_null($id)');
		assert('is_null($username) || is_string($username)');
		assert('is_null($id) || is_numeric($id)');

		$this->passwd = $dbPasswd;
		$this->db = $dbPasswd->db;
		$this->username = $username;
		$this->id = $id;
	}

	public function set($module, $key, $value) {
		if (is_object($module) && $module instanceof AbstractModule)
			$module = $module->getInstanceId();
		$this->changes[$module][$key] = $value;
		unset($this->removes[$module][$key]);
	}
	public function get($module, $key) {
		if (is_object($module) && $module instanceof AbstractModule)
			$module = $module->getInstanceId();
		$this->lazyLoad();
		if (isset($this->changes[$module][$key]))
			return $this->changes[$module][$key];
		if (isset($this->userField[$module][$key]))
			return $this->userField[$module][$key];
	}
	public function __get($module) {
		if (is_object($module) && $module instanceof AbstractModule)
			$module = $module->getInstanceId();
		$this->lazyLoad();
		if (isset($this->changes[$module]))
			$result = array_merge($this->userField[$module], $this->changes[$module]);
		else
			$result = $this->userField[$module];
		if (isset($this->removes[$module]))
			foreach($this->removes[$module] as $remove)
				unset($result[$remove]);
		return $result;
	}
	public function getModule($module) {
		if (is_object($module) && $module instanceof AbstractModule)
			$module = $module->getInstanceId();
		$this->lazyLoad();
		if (isset($this->userField[$module]))
			return $this->userField[$module];
	}
	public function exists($module, $key) {
		if (is_object($module) && $module instanceof AbstractModule)
			$module = $module->getInstanceId();
		$this->lazyLoad();
		return !isset($this->removes[$module][$key]) && (isset($this->userField[$module][$key]) || isset($this->changes[$module][$key]));
	}
	public function remove($module, $key) {
		if (is_object($module) && $module instanceof AbstractModule)
			$module = $module->getInstanceId();
		$this->removes[$module][$key] = $key;
		unset($this->changes[$module][$key]);
	}

	public function destroy() {
		$this->lazyLoad();
		$users = $this->db->query('SELECT `username`, `id` FROM `user` WHERE `id` = $id AND `username` = $username;', array(
				'id' => $this->getId(),
				'username' => $this->getUserName(),
			));
		assert('$users[0]["username"] == $this->username');
		assert('$users[0]["id"] == $this->id');
		$this->db->delete('member', array(
				'user' => $this->getId(),
			));
		$this->db->delete('userfield', array(
				'user' => $this->getId(),
			));
		$this->db->delete('user', array(
				'id' => $this->getId(),
				'username' => $this->getUserName(),
			));
	}
	public function save() {
		if (!$this->id)
			$this->lazyLoad();
		if (!$this->id)
			$this->id = $this->db->insert('user', array(
					'username' => $this->username,
				));
		foreach($this->removes as $module => $removes) {
			foreach($removes as $remove) {
				unset($this->userField[$module][$remove]);
				unset($this->changes[$module][$remove]);
				unset($this->removes[$module][$remove]);
				$this->db->delete('userfield', array(
						'module' => $module,
						'user' => $this->id,
						'key' => $remove,
					));
			}
		}
		foreach($this->changes as $module => $changes) {
			foreach($changes as $key => $value) {
				unset($this->changes[$module][$key]);
				$this->userField[$module][$key] = $value;
				$this->db->replace('userfield', array(
						'module' => $module,
						'user' => $this->id,
						'key' => $key,
						'value' => $value,
					));
			}
		}
	}
	public function setLastLogin($time=NULL) {
		if (is_null($time))
			$time = time();
		assert('is_integer($time)');
		$this->db->update('user', array(
				'lastLogin' => $time,
			), array(
				'id' => $this->getId(),
				'username' => $this->getUserName(),
			));
	}
	public function getLastLogin() {
		$lastLogin = $this->db->query('SELECT `lastLogin` FROM `user` WHERE `id` = $id;', array(
				'id' => $this->getId(),
			));
		return (int)$lastLogin[0]['lastLogin'];
	}
	public function checkPassword($password) {
		$this->lazyLoad();
		$passwords = $this->db->query('SELECT `password`, `lastChange`, `passwordExpires`, `accountExpires` FROM `user` WHERE `id` = $id;', array(
				'id' => $this->getId(),
			));
		if (!isset($passwords[0]))
			return false;
		$crypt = $this->encrypt($password, $passwords[0]['lastChange'], $passwords[0]['password']);
		return $crypt == $passwords[0]['password'];
	}
	public function resetPassword() {
		$this->db->update('user', array(
				'password' => NULL,
				), array(
				'id' => $this->getId(),
			));
	}
	public function setPassword($password) {
		$lastChange = time();
		$passwords = $this->db->query('SELECT `changeLock`, `changeDeadline`, `accountExpires` FROM `user` WHERE `id` = $id;', array(
				'id' => $this->id,
		));
		if (isset($passwords[0])) {
			if ($passwords[0]['changeLock'] > 0 && $passwords[0]['changeLock'] > $lastChange)
				user_error('Password cannot be changed before '.date(\DateTime::RFC1036, $passwords[0]['changeLock']));
			if ($passwords[0]['changeDeadline'] > 0 && $passwords[0]['changeDeadline'] < $lastChange)
				user_error('Password had to be changed before '.date(\DateTime::RFC1036, $passwords[0]['changeDeadline']));
			if ($passwords[0]['accountExpires'] > 0 && $passwords[0]['accountExpires'] < $lastChange)
				user_error('Account expired on '.date(\DateTime::RFC1036, $passwords[0]['accountExpires']));
		}
		assert('isset($this->id)');
		$encPassword = $this->encrypt($password, $lastChange);
		assert('!is_null($encPassword)');
		$this->db->update('user', array(
				'password' => $encPassword,
				'lastChange' => $lastChange,
			), array(
				'id' => $this->getId(),
				'username' => $this->getUserName(),
			));
	}
	protected static function encrypt($password, $lastChange, $hash=NULL) {
		if (is_null($hash)) {
			if (CRYPT_SHA512)
				$salt = '$6$rounds=5000$';
			else if (CRYPT_SHA256)
				$salt = '$5$rounds=5000$';
			else if (CRYPT_BLOWFISH)
				$salt = '$2y$07$'; // PHP 5.3.7 and later should use "$2y$"; Exxprezzo requires PHP 5.4
			else if (CRYPT_MD5)
				$salt = '$1$';
			else user_error('No encryption method available.');
		} else {
			$salt = substr($hash, 0, strrpos($hash, '$'));
			$salt = substr($salt, 0, strrpos($salt, '$')) . '$';
		}
		mt_srand($lastChange);
		$salt .= base64_encode(pack('LLL', mt_rand(), mt_rand(), mt_rand()));
		$salt .= '$';
		$crypt = preg_replace('_\$([^\$]+)\$(?=[^\$]+$)_', '$$', crypt($password, $salt));
		return $crypt;
	}

	public function isInGroup($group) {

	}
	public function addToGroup($group) {

	}
	public function removeFromGroup($group) {

	}
	public function getGroups() {

	}

	public function getId() {
		$this->lazyLoad();
		return $this->id;
	}
	public function getUserName() {
		if (!isset($this->username)) {
			$users = $this->db->query('SELECT `username`, `realname`, `accountExpires` FROM `user` WHERE `id` = $id;', array(
					'id' => $this->id,
				));
			$this->username = $users[0]['username'];
			$this->realname = $users[0]['realname'];
			if ($users[0]['accountExpires'] && $users[0]['accountExpires'] < time())
				user_error('Account '.$this->username.' is expired.');
		}
		assert(isset($this->username));
		return $this->username;
	}
	public function getRealName() {
		if (!isset($this->realname)) {
			$this->lazyLoad();
			$this->getUserName();
		}
		assert(isset($this->realname));
		if (!$this->realname)
			$this->realname = $this->getUserName();
		return $this->realname;
	}

	protected function lazyLoad() {
		if (!isset($this->id)) {
			$users = $this->db->query('SELECT `id`, `realname`, `accountExpires` FROM `user` WHERE `username` = $username;', array(
					'username' => $this->username,
				));
			if ($users) {
				$this->id = (int)$users[0]['id'];
				$this->realname = $users[0]['realname'];
				if (isset($users[0]['accountExpires']) && $users[0]['accountExpires'] < time())
					user_error('Account "'.$this->username.'" is expired.');
			}
		}

		if (isset($this->id)) {
			$userField = $this->db->query('SELECT `key`, `value`, `module` FROM `userfield` WHERE `user` = $id', array(
					'id' => $this->id,
				));
			foreach($userField as $entry)
				$this->userField[$entry['module']][$entry['key']] = $entry['value'];
		}
	}

}
