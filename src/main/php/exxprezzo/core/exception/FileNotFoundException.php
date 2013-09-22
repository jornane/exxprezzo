<?php namespace exxprezzo\core\exception;

class FileNotFoundException extends ItemNotFoundException {

	public function __construct($file, $code = 404, Exception $previous = null) {
		$this->file = $file;
		parent::__construct($file, 'file', $code, $previous);
	}

}