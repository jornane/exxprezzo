<?php namespace exxprezzo\core\output;

use \exxprezzo\core\Template;

class BlockOutput extends AbstractFormattableOutput implements PartialOutput {
	protected $template;
	
	public function __construct($source, $content) {
		parent::__construct($source, $content);
	}
	
	/**
	 * 
	 * @param Template $template
	 */
	public function setTemplate($template) {
		$template->setContent($this->content);
		$this->template = $template;
	}
	
	public function getContentType() {
		return 'text/html';
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
