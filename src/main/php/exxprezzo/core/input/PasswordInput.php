<?php namespace exxprezzo\core\input;

use \exxprezzo\core\type\SafeHtml;

class PasswordInput extends AbstractInput implements SafeHtml {

	public function __construct($name, $value='') {
		parent::__construct($name, $value);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see exxprezzo\core\input.Input::__toString()
	 * @todo use templates to generate the string
	 */
	public function __toString() {
		return '<input type="password" name="'.htmlspecialchars($this->name).'" value="'.htmlspecialchars($this->value).'" />';
	}

}
