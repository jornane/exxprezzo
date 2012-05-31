<?php namespace exxprezzo\core\output;

use \exxprezzo\core\Template;

class ContentOutput extends AbstractOutput implements PartialOutput, FormattableOutput {
	
	protected $content;
	protected $template;
	
	public function __construct($source, $content) {
		parent::__construct($source);
		$this->content = $content;
	}
	
	/**
	 * 
	 * @param Template $template
	 */
	public function setTemplate($template) {
		$template->setContent($this->content);
		$this->template = $template;
	}
	
	public function getContentTypes() {
		return array('text/html');
	}
	
	/**
	 * @return string
	 */
	public function getContent() {
		if (is_null($this->template))
			user_error('No template specified');
		return $this->template->render();
	}
	
}
