<?php namespace exxprezzo\core\output;

use \DateTime;

use \exxprezzo\core\module\AbstractModule;

abstract class AbstractOutput implements Output {
	
	private $source;
	
	/**
	 * 
	 * @param AbstractModule $source
	 */
	public function __construct($source) {
		$this->source = $source;
	}
	
	/**
	 * Send this Output to the browser rightaway
	 * 
	 * (non-PHPdoc)
	 * @see exxprezzo\core.Runnable::run()
	 */
	public function run() {
		header('Last-Modified: '.gmdate(DateTime::RFC1123, $this->getLastModified()->getTimestamp()));
		header('Pragma: '.($this->isCacheable()?'cache':'no-cache'));
		header('Cache-control: '.($this->isCacheable()
				? 'max-age='.($this->getExpiryDate()->getTimestamp()-$this->getLastModified()->getTimestamp())
				: 'no-cache, must-revalidate')
			);
		header('Expires: '.gmdate(DateTime::RFC1123, $this->isCacheable() ? $this->getExpiryDate()->getTimestamp() : $this->getLastModified()->getTimestamp()));
		echo $this->getContent();
	}
	
	/**
	 * @return AbstractModule
	 */
	public function getSource() {
		return $this->source;
	}
	
	/**
	 * @return string
	 */
	public abstract function getContent();
	
	/**
	 * @return string[]
	 */
	public abstract function getContentTypes();
	
	/**
	 * @return int
	 */
	public function getLength() {
		return strlen($this->getContent());
	}
	
	/**
	 * @return \DateTime
	 */
	public function getLastModified() {
		return new DateTime();
	}
	
	/**
	 * @return \DateTime
	 */
	public function getExpiryDate() {
		return new DateTime();
	}
	
	/**
	 * @return boolean
	 */
	public function isCacheable() {
		return false;
	}
	
	public final function __toString() {
		return $this->getContent();
	}
	
}
