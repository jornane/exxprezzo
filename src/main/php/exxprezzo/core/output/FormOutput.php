<?php namespace exxprezzo\core\output;

use \exxprezzo\core\Content;
use \exxprezzo\core\Template;

abstract class FormOutput extends BlockOutput {
	/**	@var Content */
	protected $formContent;
	/** @var FormattableOutput */
	protected $blockOutput;
	
	/**
	 * 
	 * @param FormattableOutput $formOutput
	 * @param string $action
	 * @param boolean $multipart
	 */
	public function __construct($blockOutput, $action, $multipart=false) {
		parent::__construct($blockOutput->getSource(), $this->formContent = new Content());
		if ($blockOutput instanceof FormOutput)
			user_error('Forms cannot be nested');
		$this->blockOutput = $blockOutput;
		
		$this->template = new Template('<form action="{action}" method="{method}" enctype="{enctype}">{blockOutput}</form>');
		$this->formContent->putVariables(array(
				'action' => $action,
				'method' => static::method,
				'enctype' => $multipart ? 'multipart/form-data' : 'application/x-www-form-urlencoded',
				'blockOutput' => $blockOutput,
			));
		$this->template->setContent($this->formContent);
	}
	
	/**
	 * 
	 * @param Template $template
	 */
	public function setTemplate($template) {
		if ($this->blockOutput instanceof BlockOutput)
			$this->blockOutput->setTemplate($template);
	}
	
	public function getContentType() {
		$this->blockOutput->getContentType();
	}
	
	/**
	 * @return string
	 */
	public function getContent() {
		if (is_null($this->template))
			user_error('No template specified');
		return $this->template->render();
	}
	
	public function getContentObject() {
		return $this->formContent->getContentObject();
	}
	
}
