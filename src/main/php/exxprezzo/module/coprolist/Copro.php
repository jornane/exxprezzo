<?php namespace exxprezzo\module\coprolist;
use \exxprezzo\core\Content;

class Copro
{
	// properties
	private $name = 'Pietje Puk';
	private $commissions = array();
	private $photo = 'pietjepuk.jpg';
	public static $imagepath = 'template/default/module/coprolist/resources/';

	// Constructor
	public function  __construct($name, $photo, $commissions){
		$this->name=$name;
		$this->commissions=$commissions;
		$this->photo=$photo;
	}
	
	// Getters
	public function getName(){
		return $this->name;
	}
	
	public function getCommissions(){
		//TODO return commissie-object
		return $this->commissions;
	}
	
	public function getPhoto(){
		return $this->photo;
	}
	
	public function getPhotoUrl(){
		return static::$imagepath.$this->photo;
	}
}
?>