<?php namespace exxprezzo\core\input;

use \exxprezzo\core\type\SafeHtml;

class LongTextInput extends AbstractInput implements SafeHtml {

	/**
	 * (non-PHPdoc)
	 * @see exxprezzo\core\input.Input::__toString()
	 * @todo use templates to generate the string
	 */
	public function __toString() {
		return '<textarea name="'.htmlspecialchars($this->name).'">'.htmlspecialchars($this->value).'</textarea>';
	}

}
