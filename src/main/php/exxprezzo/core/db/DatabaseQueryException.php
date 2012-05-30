<?php namespace exxprezzo\core\db;

use \Exception;

class DatabaseQueryException extends Exception {
	protected $query;
	protected $errno;
	protected $error;
	
	public function __construct($db, Exception $previous = NULL) {
		$this->query = $db->lastquery;
		$this->errno = $db->getErrno();
		$this->error = $db->getError();
		$errstr = "An error occurred while executing the following query:\n".$this->query."\n\n".$this->error;
		/* $errstr = Core::readvar('core.developer')
			? "An error occurred while executing the following query:\n".$this->query."\n\n".$this->error
			: 'Failed to execute query'; */
		//parent::__construct($errstr, (integer)$this->errno, $previous);
		parent::__construct($errstr, (integer)$this->errno);
	}
}