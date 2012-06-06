<?php namespace exxprezzo\core\input;

interface Input {
	
	/**
	 * @return string
	 */
	function getName();
	
	/**
	 * @return mixed
	 */
	function getValue();
	
	/**
	 * @return string
	 */
	function __toString();
	
}
