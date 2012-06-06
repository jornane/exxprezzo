<?php namespace exxprezzo\core\input;

class LongTextInput extends AbstractInput {

	/**
	 * (non-PHPdoc)
	 * @see exxprezzo\core\input.Input::__toString()
	 * @todo use templates to generate the string
	 */
	public function __toString() {
		return '<textarea name="'.htmlspecialchars($this->name).'">'.htmlspecialchars($this->value).'</textarea>';
	}

}
