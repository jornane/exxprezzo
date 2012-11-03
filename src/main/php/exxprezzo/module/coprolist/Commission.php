<?php namespace exxprezzo\module\coprolist;
use \exxprezzo\core\Content;

class Commission
{
	// Properties
	private $name;
	private $DN;
	
	// Constructor
	public function  __construct($name, $DN){
		$this->name=$name;
		$this->DN=$DN;
	}
	
	// Getters
	public function getName(){
		return $this->name;
	}
	
	public function getLink(){
		return $this->name;
	}
	
	public function getDN(){
		return $this->DN;
	}
	
	public function __toString()
	{
		return $this->getName().' ('.$this->getDN().')';
	}
}