<?php namespace exxprezzo\core;

class Content {

	/** @var string[] */
	protected $vars = array();
	/** @var Content[][] */
	protected $loops = array();
	/** @var Content[] */
	protected $namespaces = array();
		
	public function putVariable($key, $value) {
		$this->vars[$key] = $value;
	}
	public function putVariables($variables) {
		$this->vars = array_merge($this->vars, $variables);
	}
	
	/**
	 * 
	 * @param string $loopName
	 */
	protected function &getLoop($loopName) {
		$loops = explode('.', $loopName);
		$last = array_pop($loops);
		$root = &$this->loops;
		foreach($loops as $loopName) {
			$root = &$root[$loopName];
			$root = &$root[count($root)-1]->loops;
		}
		return $root[$last];
	}
	
	/**
	 * 
	 * @param string $loopName
	 * @param string[]|Content $loop
	 */
	public function addLoop($loopName, $loop) {
		$parentLoop = &$this->getLoop($loopName);
		if (is_object($loop) && $loop instanceof Content) {
			$parentLoop[] = $loop;
		} else if (is_array($loop)) {
			$content = new Content();
			$content->vars = $loop;
			$parentLoop[] = $content;
		} else {
			user_error('Invalid type for $loop: '.gettype($loop));
		}
		
	}
	/**
	 * 
	 * @param string $loopName
	 * @param string[] $variables
	 */
	public function appendLoop($loopName, $variables) {
		$parentLoop = &$this->getLoop($loopName);
		$parentLoop[count($parentLoop)-1]->vars
			= array_merge(
					$parentLoop[count($this->loops)-1]->vars,
					$variables
				);
	}
	
	/**
	 * 
	 * @param string $name
	 */
	public function getVariableString($name) {
		return isset($this->vars[$name]) && is_object($this->vars[$name])
				? $this->vars[$name]->__toString()
				: $this->getVariable($name)
			;
	}
	
	/**
	 * 
	 * @param string $name
	 */
	public function getVariable($name) {
		return isset($this->vars[$name]) ? $this->vars[$name] : NULL;
	}
	
	/**
	 * 
	 * @param string $name
	 * @return Content
	 */
	public function getLoops($name) {
		return isset($this->loops[$name]) ? $this->loops[$name] : NULL;
	}
	
	/**
	 * @access protected
	 * This method is namespace protected
	 *
	 * @param string $loopName
	 * @param int $iteration
	 */
	public function loopMerge($loopName, $iteration) {
		$result = clone $this;
		foreach($result->loops[$loopName][$iteration]->vars as $key => $value)
			$result->vars[$loopName.'.'.$key] = $value;
		foreach($result->loops[$loopName][$iteration]->loops as $key => $value)
			$result->loops[$loopName.'.'.$key] = $value;
		unset($result->loops[$loopName]);
		return $result;
	}
	
	/**
	 * 
	 * @param string $name
	 * @param Content $content
	 */
	public function putNamespace($name, $content) {
		if ($content instanceof Content)
			$this->namespaces[$name] = $content;
		else
			user_error('$content should be of type Content');
	}
	
	public function getNamespace($name) {
		return $this->namespaces[$name];
	}
	
}
