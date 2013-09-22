<?php namespace exxprezzo\core\exception;

class DatabaseQueryException extends ExxprezzoException {
	protected $query;
	protected $errno;
	protected $error;
	
	public function __construct($db, $code = 500, Exception $previous = NULL) {
		$this->query = $db->lastquery;
		$this->errno = $db->getErrno();
		$this->error = $db->getError();
		$errstr = "An error occurred while executing the following query:\n".$this->query."\n\n".$this->error;
		parent::__construct($errstr, $code, $previous);
	}

	public function getQuery() {
		return $this->query;
	}

	public function getErrno() {
		return $this->errno;
	}

	public function getError() {
		return $this->error();
	}
}