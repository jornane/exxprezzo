<?php namespace exxprezzo\core\input;

abstract class AbstractInput implements Input {
	
	/** @var string */
	protected $name;
	/** @var mixed */
	protected $value;
	
	/**
	 * 
	 * @param string $name
	 * @param mixed $value
	 */
	public function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see exxprezzo\core\input.Input::getName()
	 */
	public function getName() {
		return $this->name;
	}
	/**
	 * (non-PHPdoc)
	 * @see exxprezzo\core\input.Input::getValue()
	 */
	public function getValue() {
		return $this->value;
	}
	
}
