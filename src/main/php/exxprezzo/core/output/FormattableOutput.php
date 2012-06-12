<?php namespace exxprezzo\core\output;

interface FormattableOutput extends Output {
	
	/**
	 * Gets the formattable Content object associated with
	 * this FormattableOutput. This should be regarded as
	 * a final field and implementations are encouraged to 
	 * prevent modification of their content object. This
	 * does not mean that the returned value must be 
	 * unmodifiable
	 * @return Content The Content object this FormattableOuput 
	 * provides formatting for
	 */
	function getContentObject();
	
}