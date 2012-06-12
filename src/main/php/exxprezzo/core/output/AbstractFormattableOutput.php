<?php namespace exxprezzo\core\output;

use exxprezzo\core\output\FormattableOutput;
use exxprezzo\core\output\AbstractOutput;

abstract class AbstractFormattableOutput extends AbstractOutput implements FormattableOutput {
	
	protected $content;
	
	public function __construct($source, $content) {
		parent::__construct($source);
		$this->content = $content;
	}
	
	public function getContentObject() {
		return clone $this->content;
	}
	
}