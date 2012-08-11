<?php namespace exxprezzo\module\passwd;

use \exxprezzo\core\module\AbstractModule;

abstract class Passwd extends AbstractModule {
	
	public abstract function getUsers();
	public abstract function getGroups();
	public abstract function getUserById($id);
	public abstract function getGroupById($id);
	public abstract function getUserByName($name);
	public abstract function getGroupByName($name);
	
	public abstract function addUser($name);
	public abstract function addGroup($name);
	
	public abstract function viewUser($params, $content);
	public abstract function viewGroup($params, $content);
	public abstract function login($params, $content);
	public abstract function logout($params, $content);
	
}
