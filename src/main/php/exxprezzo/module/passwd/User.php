<?php namespace exxprezzo\module\passwd;

interface User {

	function __set($name, $value);
	function __get($name);
	function __isset($name);
	function __unset($name);
	
	function getId();
	function getUserName();
	
	function destroy();
	function checkPassword($password);
	
	function isInGroup($group);
	function addToGroup($group);
	function removeFromGroup($group);
	function getGroups();
	
}