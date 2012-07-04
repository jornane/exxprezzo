<?php namespace exxprezzo\core\output;

use \exxprezzo\core\output\FormattableOutput;
use \exxprezzo\core\output\AbstractOutput;

abstract class AbstractFormattableOutput extends AbstractOutput implements FormattableOutput {
	
	/** @var Content $content */
	protected $content;
	protected $preferredTemplate;
	
	/**
	 * 
	 * @param AbstractModule $source
	 * @param Content $content
	 * @param string $preferredTemplate
	 */
	public function __construct($source, $content, $preferredTemplate=NULL) {
		parent::__construct($source);
		$this->content = $content;
		$this->preferredTemplate = $preferredTemplate;
	}
	
	public function getPreferredTemplate() {
		return $this->preferredTemplate;
	}
	
	public function getContentObject() {
		return clone $this->content;
	}
	
}
