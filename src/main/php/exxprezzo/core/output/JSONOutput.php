<?php namespace exxprezzo\core\output;

use \exxprezzo\core\output\AbstractFormattableOutput;

class JSONOutput extends AbstractFormattableOutput {
	
	private $json = NULL;
	
	
	function __construct($source, $content){
		parent::__construct($source, $content);
	}
	
	function getContent() {
		if(!$this->json)
			$this->json = $this->buildJSON($this->content);
		return $this->json;
	}
	
	function  getContentType() {
		return 'json/application';
	}
	
	private function buildJSON($content) {
		return json_encode($content);
	}
}