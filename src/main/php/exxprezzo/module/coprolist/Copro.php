<?php namespace exxprezzo\module\coprolist;
use \exxprezzo\core\Content;

class Copro
{
	// properties
	private $name = 'Pietje Puk';
	private $commissions = array();
	private $photo = 'pietjepuk.jpg';
	public static $imagepath = 'https://www.iapc.utwente.nl/intern/cooperanten/';
	private $board;
	
	// Constructor
	public function  __construct($name, $photo, $commissions,$board){
		$this->name=$name;
		$this->commissions=$commissions;
		$this->photo=$photo;
		$this->board=$board;
	}
	
	// Getters
	public function getName(){
		return $this->name;
	}
	
	public function getCommissions(){
		//TODO return commissie-object
		return $this->commissions;
	}
	
	public function getBoard(){
		return $this->board;
	}
	
	public function getPhoto(){
		return $this->photo;
	}
	
	public function getPhotoUrl(){
		return static::$imagepath.$this->photo;
	}
}
?>