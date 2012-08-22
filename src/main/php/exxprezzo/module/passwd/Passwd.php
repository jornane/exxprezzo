<?php namespace exxprezzo\module\passwd;

use \exxprezzo\core\module\AbstractModule;

abstract class Passwd extends AbstractModule {
	
	/**
	 * @return User[]
	 */
	public abstract function getUsers();
	/**
	 * @return Group[]
	 */
	public abstract function getGroups();
	/**
	 * 
	 * @param unknown_type $id
	 * @return User
	 */
	public abstract function getUserById($id);
	/**
	 * 
	 * @param unknown_type $id
	 * @return Group
	 */
	public abstract function getGroupById($id);
	/**
	 * 
	 * @param unknown_type $name
	 * @return User
	 */
	public abstract function getUserByName($name);
	/**
	 * 
	 * @param unknown_type $name
	 * @return Group
	 */
	public abstract function getGroupByName($name);
	
	/**
	 * 
	 * @param unknown_type $params
	 * @param unknown_type $content
	 */
	public abstract function viewUser($params, $content);
	/**
	 * 
	 * @param unknown_type $params
	 * @param unknown_type $content
	 */
	public abstract function viewGroup($params, $content);
	/**
	 * 
	 * @param unknown_type $params
	 * @param unknown_type $content
	 */
	public abstract function login($params, $content);
	
}
