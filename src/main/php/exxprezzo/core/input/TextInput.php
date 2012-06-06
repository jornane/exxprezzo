<?php namespace exxprezzo\core\input;

class TextInput extends AbstractInput {

	/**
	 * (non-PHPdoc)
	 * @see exxprezzo\core\input.Input::__toString()
	 * @todo use templates to generate the string
	 */
	public function __toString() {
		return '<input type="text" name="'.htmlspecialchars($this->name).'" value="'.htmlspecialchars($this->value).'" />';
	}

}
