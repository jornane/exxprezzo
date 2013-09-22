<?php namespace exxprezzo\core\input;

use \exxprezzo\core\type\SafeHtml;

class ButtonInput extends AbstractInput implements SafeHtml {

	/**
	 * (non-PHPdoc)
	 * @see exxprezzo\core\input.Input::__toString()
	 * @todo use templates to generate the string
	 */
	public function __toString() {
		return '<input type="submit" name="'.htmlspecialchars($this->name).'" value="'.htmlspecialchars($this->value).'" />';
	}

}
