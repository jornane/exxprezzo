<?php namespace exxprezzo\core\input;

use \exxprezzo\core\type\SafeHtml;

class FileInput extends AbstractInput implements SafeHtml {

	public function __construct($name) {
		parent::__construct($name, NULL);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see exxprezzo\core\input.Input::__toString()
	 * @todo use templates to generate the string
	 */
	public function __toString() {
		return '<input type="file" name="'.htmlspecialchars($this->name).'" />';
	}

}
