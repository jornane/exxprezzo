<?php namespace exxprezzo\core\input;

class Form {
	
	protected $action;
	protected $method;
	protected $encType;
	protected $acceptCharset;
	protected $accept;
	
	public function __construct($action, $method='post', $encType='application/x-www-form-urlencoded', $acceptCharset='UTF-8', $accept=array('*/*')) {
		$this->action = $action;
		$this->method = $method;
		$this->encType = $encType;
		$this->acceptCharset = $acceptCharset;
		$this->accept = $accept;
	}
	
	public function getAction() {
		return $this->action;
	}
	
	public function getMethod() {
		return $this->method;
	}
	
	public function getEncType() {
		return $this->encType;
	}
	
	public function getAcceptCharset() {
		return $this->acceptCharset;
	}
	
	public function getAccept() {
		return $this->accept;
	}
	
	public function getFooter() {
		return '</form>';
	}
	
	public function getHeader() {
		$result = '<form action="'.htmlspecialchars($this->action).'"';
		if (strtolower($this->method) != 'get')
			$result .= ' method="'.htmlspecialchars($this->method).'"';
		if (strtolower($this->encType) != 'application/x-www-form-urlencoded')
			$result .= ' enctype="'.htmlspecialchars($this->enctype).'"';
		if (strtolower($this->acceptCharset) != 'utf-8')
			$result .= ' accept-charset="'.htmlspecialchars($this->acceptCharset).'"';
		if ($this->accept != array('*/*'))
			$result .= ' accept="'.htmlspecialchars(implode(',', $this->accept)).'"';
		return $result.'>';
	}
	
}
