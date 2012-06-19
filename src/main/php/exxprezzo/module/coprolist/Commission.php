<?php namespace exxprezzo\module\coprolist;
use \exxprezzo\core\Content;

class Commission
{
	// Properties
	private $name;
	
	// Constructor
	public function  __construct($name){
		$this->name=$name;
	}
	
	// Getters
	public function getName(){
		return $this->name;
	}
}