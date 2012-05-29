<?php namespace exxprezzo\core\output;

class PartialStringOutput extends AbstractOutput implements PartialOutput {
	
	protected $string;
	protected $contentTypes;
	
	public function __construct($source, $contentTypes, $string) {
		parent::__construct($source);
		$this->string = $string;
		$this->contentTypes = $contentTypes;
	}
		
	public function getContent() {
		return $this->string;
	}
	
	public function getContentTypes() {
		return $this->contentTypes;
	}
	
}