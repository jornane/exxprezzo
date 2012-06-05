<?php namespace exxprezzo\core\output;

interface FormattableOutput extends Output {
	
	/**
	 * @return Content
	 */
	function getContentObject();
	
}