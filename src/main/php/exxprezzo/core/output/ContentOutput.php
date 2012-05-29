<?php namespace exxprezzo\core\output;

use exxprezzo\core\Template;

class ContentOutput extends AbstractOutput implements PartialOutput, FormattableOutput {
	
	protected $content;
	protected $template;
	
	public function __construct($content) {
		$this->content = $content;
	}
	
	public function getContent() {
		return $this->content;
	}
	
	/**
	 * 
	 * @param Template $template
	 */
	public function setTemplate($template) {
		$template->setContent($this->getContent());
		$this->template = $template;
	}
	
	public function getContentTypes() {
		return array('text/html');
	}
	
	/**
	 * @return AbstractModule
	 */
	public function getSource() {
		
	}
	
	/**
	 * @return string
	 */
	public function getContents() {
		
	}
	
}
