<?php namespace exxprezzo\core\db;

use \Exception;

class DatabaseConnectionException extends Exception {
	public function __construct($errstr, Exception $previous = NULL) {
		parent::__construct($errstr, 0, $previous);
	}
}