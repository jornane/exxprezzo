<?php namespace exxprezzo\core;

class Content {

	/** @var string[] */
	protected $vars;
	/** @var Content[] */
	protected $blocks;
	
	public function getVariable($name) {
		return $this->vars[$name];
	}
	
	public function getBlock($name) {
		return $this->blocks[$name];
	}
	
}