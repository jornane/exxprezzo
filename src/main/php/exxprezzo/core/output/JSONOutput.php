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
		return json_encode($this->_buildJSON($content));
	}
	
	private function _buildJSON($content) {
		$result = array();
		foreach ($content->getVariableNames() as $value) {
			if($content->getVariable($value) instanceof Content) {
				$result[$value] = $this->_buildJSON($content->getVariable($value));
			} else {
				// FIXME: Is this correct? Are there other possibilities
				// and how should they be handled
				$result[$value] = $content->getVariableString($value);
			}
		}
		return $result;
	}
}