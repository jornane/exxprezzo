<?php namespace exxprezzo\core\exception;

class PermissionException extends ExxprezzoException {
	protected $permission = NULL;
	
	public function __construct($permissionName, $code = 403, Exception $previous = null) {
		$this->permission = $permissionName;
		parent::__construct('No permission for '.$permissionName, $code);
	}
	
	public function getPermissionName() {
		return $this->permission;
	}
}
