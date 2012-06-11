<?php namespace exxprezzo\core\output;

class ExceptionOutput extends AbstractOutput implements PartialOutput {
	
	public function __construct($source, $exception) {
		parent::__construct($source);
		$this->exception = $exception;
	}
	
	public function getContent() {
		
	}
	
	public function getContentTypes() {
		
	}
	
	
}
