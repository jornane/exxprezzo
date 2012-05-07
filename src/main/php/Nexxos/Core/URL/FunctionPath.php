<?php
namespace Nexxos\Core\URL {
	class FunctionPath {
		public final $hostGroup;
		public final $path;
		public final $get;
		
		public function __construct($hostGroup, $path, $get=array()) {
			$this->hostGroup = $hostGroup;
			$this->path = $path;
			$this->get = $get;
		}
	}
}