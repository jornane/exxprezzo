<?php namespace exxprezzo\core\exception;

use \Exception;

class ExxprezzoException extends Exception {

	public function __construct($message = "", $code = 500, Exception $previous = NULL) {
		parent::__construct($message, $code, $previous);
	}

	public function writeErrorHeader() {
		header('HTTP/1.0 '.(int)$this->getCode(), true, $this->getCode());
	}

}