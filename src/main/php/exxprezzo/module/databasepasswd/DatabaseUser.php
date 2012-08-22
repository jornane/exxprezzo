<?php namespace exxprezzo\module\databasepasswd;

use \exxprezzo\module\passwd\User;

class DatabaseUser implements User {

	protected $username = NULL;
	protected $id = 0;
	protected $data = array();
	protected $changes = array();
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
	
	public function __set($key, $value) {
		$changes[$key] = $value;
		unset($removes[$key]);
	}
	public function __get($key) {
		$this->lazyLoad();
		if (isset($changes[$key]))
			return $changes[$key];
		if (isset($data[$key]))
			return $data[$key];
	}
	public function __isset($key) {
		$this->lazyLoad();
		return !isset($removes[$key]) && (isset($data[$key]) || isset($changes[$key]));
	}
	public function __unset($key) {
		$removes[$key] = $key;
	}
	
	public function destroy() {
		$this->lazyLoad();
		$users = $this->db->query('SELECT `username`, `id` FROM `user` WHERE `id` = $id AND `username` = $username;', array(
				'id' => $this->id,
				'username' => $this->username,
			));
		assert('$users[0]["username"] == $this->username');
		assert('$users[0]["id"] == $this->id');
		$this->db->delete('member', array(
				'user' => $this->id,
			));
		$this->db->delete('userfield', array(
				'user' => $this->id,
			));
		$this->db->delete('user', array(
				'id' => $this->id,
				'username' => $this->username,
			));
	}
	public function save() {
		if (!$this->id)
			$this->lazyLoad();
		if (!$this->id)
			$this->id = $this->db->insert('user', array(
					'username' => $this->username,
				));
		foreach($this->removes as $remove) {
			unset($this->data[$remove]);
			unset($this->changes[$remove]);
			unset($this->removes[$remove]);
			$this->db->delete('userfield', array(
					'user' => $this->id,
					'key' => $remove,
				));
		}
		foreach($this->changes as $key => $value) {
			unset($this->changes[$key]);
			$this->data[$key] = $value;
			$this->db->replace('userfield', array(
					'user' => $this->id,
					'key' => $key,
					'value' => $value,
				));
		}
	}
	public function checkPassword($password) {
		$this->lazyLoad();
		$passwords = $this->db->query('SELECT `password`, `lastChange`, `passwordExpires`, `accountExpires` FROM `user` WHERE `id` = $id;', array(
				'id' => $this->id,
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
				'id' => $this->id,
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
		$this->db->replace('user', array(
				'id' => $this->id,
				'username' => $this->username,
				'password' => $encPassword,
				'lastChange' => $lastChange,
				'changeDeadline' => NULL,
				'deadlineWarning' => NULL,
				'passwordExpires' => NULL,
			));
	}
	protected function encrypt($password, $lastChange, $hash=NULL) {
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
		$salt .= base64_encode(pack('LLL', mt_rand(), mt_rand(), mt_rand()));
		$salt .= base64_encode(pack('LLL', mt_rand(), mt_rand(), mt_rand()));
		$salt .= base64_encode(pack('LLL', mt_rand(), mt_rand(), mt_rand()));
		$salt .= '$';
		$crypt = preg_replace('_\$([^\$]+)\$(?=[^\$]+$)_', '$$', crypt($password, $salt));
		//die($crypt);
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
			$users = $this->db->query('SELECT `username`, `accountExpires` FROM `user` WHERE `id` = $id;', array(
					'id' => $this->id,
				));
			$this->username = $users[0]['username'];
			if ($users[0]['accountExpires'] < time())
				user_error('Account ".$this->username." is expired.');
		}
		assert(isset($this->username));
		return $this->username;
	}
	
	protected function lazyLoad() {
		if (!isset($this->id)) {
			$users = $this->db->query('SELECT `id`, `accountExpires` FROM `user` WHERE `username` = $username;', array(
					'username' => $this->username,
				));
			if ($users) {
				$this->id = (int)$users[0]['id'];
				if (isset($users[0]['accountExpires']) && $users[0]['accountExpires'] < time())
					user_error('Account ".$this->username." is expired.');
			}
		}
		
		if (isset($this->id)) {
			$data = $this->db->query('SELECT `key`, `value` FROM `userfield` WHERE `user` = $id', array(
					'id' => $this->id,
				));
			foreach($data as $entry)
				$this->data[$entry['key']] = $entry['value'];
		}
	}
	
}