<?php namespace exxprezzo\module\passwd;

interface Group {

	public abstract function __set($name, $value);
	public abstract function __get($name);
	public abstract function __isset($name);
	public abstract function __unset($name);
	
	public abstract function destroy();
	
	public abstract function hasUser($user);
	public abstract function addUser($user);
	public abstract function removeUser($user);
	public abstract function getUsers();

}