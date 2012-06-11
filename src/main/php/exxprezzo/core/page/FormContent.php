<?php namespace exxprezzo\core\page;

use \exxprezzo\core\Template;

use \exxprezzo\core\output\AbstractOutput;
use \exxprezzo\core\output\FormattableOutput;

class FormOutput extends AbstractOutput {
	
	/** @var Content */
	private $content;
	
	/**
	 * 
	 * @param FormattableOutput $output
	 * @param string $theme
	 */
	public function __construct($output, $theme) {
		parent::__construct($output->getSource());
		$this->theme = $theme;
		$this->content = clone $output->getContent();
	}
	
	public abstract function getContent() {
		$tpl = Page::getTemplate('page', $this->theme, array('form'));
		$this->content->putVariable('content', $output);
		return $tpl; 
	}
	
	public abstract function getContentTypes() {
		return array('text/html');
	}
	
	
}
