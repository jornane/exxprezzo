<?php namespace exxprezzo\module\passwd;

interface User {

	function getId();
	function getUserName();
	
	function destroy();
	function checkPassword($password);
	
	function isInGroup($group);
	function addToGroup($group);
	function removeFromGroup($group);
	function getGroups();
	
}