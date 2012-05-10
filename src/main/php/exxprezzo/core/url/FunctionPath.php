<?php
namespace exxprezzo\core\url;

class FunctionPath {
	public $hostGroup;
	public $path;
	public $get;
	
	public function __construct($hostGroup, $path, $get=array()) {
		$this->hostGroup = $hostGroup;
		$this->path = $path;
		$this->get = $get;
	}
}
