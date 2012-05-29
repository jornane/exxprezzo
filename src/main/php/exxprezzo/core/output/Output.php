<?php namespace exxprezzo\core;

use exxprezzo\core\module\AbstractModule;

interface Output extends Runnable {
	
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
	function renderContents();
	
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
	
}
