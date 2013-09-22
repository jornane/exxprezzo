<?php namespace exxprezzo\core\output;

use \exxprezzo\core\type\SafeHtml;

use \exxprezzo\core\Runnable;

interface Output extends Runnable, SafeHtml {
	
	/**
	 * Send this Output to the browser rightaway
	 *
	 * (non-PHPdoc)
	 * @see exxprezzo\core.Runnable::run()
	 */
	function run();
	
	/**
	 * @return AbstractModule
	 */
	function getSource();
	
	/**
	 * @return string
	 */
	function getContent();
	
	/**
	 * @return string
	 */
	function getContentType();
	
	/**
	 * @return int
	 */
	function getLength();
	
	/**
	 * @return \DateTime
	 */
	function getLastModified();
	
	/**
	 * @return \DateTime
	 */
	function getExpiryDate();
	
	/**
	 * @return boolean
	 */
	function isCacheable();
	
	function __toString();
	
}