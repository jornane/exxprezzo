<?php namespace exxprezzo\module\databasepasswd;

use \exxprezzo\module\passwd\Group;

class DatabaseGroup implements Group {

	protected $groupname = NULL;
	protected $id = 0;
	protected $data = array();
	protected $changes = array();
	protected $removes = array();
	
	/** @var \exxprezzo\core\db\SQL */
	protected $db;
	/** @var DatabasePasswd */
	protected $passwd;
	
	public function __construct($dbPasswd, $groupname, $id) {
		assert('$dbPasswd instanceof \exxprezzo\module\databasepasswd\DatabasePasswd');
		assert('!is_null($groupname) || !is_null($id)');
		assert('is_string($groupname)');
		assert('is_numeric($id)');
		
		$this->passwd = $passwd;
		$this->groupname = groupname;
		$this->id = $id;
	}
		
	public function __set($name, $value) {
		
	}
	public function __get($name) {
		
	}
	public function __isset($name) {
		
	}
	public function __unset($name) {
		
	}
	
	public function destroy() {
		
	}
	
	public function hasUser($user) {
		
	}
	public function addUser($user) {
		
	}
	public function removeUser($user) {
		
	}
	public function getUsers() {
		
	}

}