<?php namespace exxprezzo\core\output;

class StringOutput extends AbstractOutput {
	
	protected $string;
	protected $contentType;
	
	public function __construct($source, $contentType, $string) {
		parent::__construct($source);
		$this->string = $string;
		$this->contentType = $contentType;
	}
	
	public function getContent() {
		return $this->string;
	}
	
	public function getContentType() {
		return $this->contentType;
	}
	
}