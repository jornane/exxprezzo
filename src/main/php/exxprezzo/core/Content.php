<?php namespace exxprezzo\core;

class Content implements \JsonSerializable {

	/** @var (string|\exxprezzo\core\Content[]|object)[] */
	protected $vars = array();
	/** @var Content[] */
	protected $namespaces = array();
		
	public function putVariable($key, $value) {
		$this->vars[$key] = $value;
	}
	public function putVariables($variables) {
		$this->vars = array_merge($this->vars, $variables);
	}
	public function removeVariable($key) {
		unset($this->vars[$key]);
	}
	
	/**
	 * 
	 * @param string $loopName
	 */
	protected function &getLoop($loopName) {
		$loops = explode('.', $loopName);
		$last = array_pop($loops);
		$root = &$this->vars;
		foreach($loops as $loopName) {
			$root = &$root[$loopName];
			$root = &$root[count($root)-1]->vars;
		}
		return $root[$last];
	}
	
	/**
	 * 
	 * @param string $loopName
	 * @param string[]|object $loop
	 */
	public function addLoop($loopName, $loop) {
		$parentLoop = &$this->getLoop($loopName);
		if (is_object($loop)) {
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
	 * @param string $name
	 */
	public function getVariableString($name) {
		return isset($this->vars[$name]) && is_object($this->vars[$name])
				? (method_exists($this->vars[$name], '__toString') ? $this->vars[$name]->__toString() : get_class($this->vars[$name]))
				: $this->getVariable($name)
			;
	}
	
	/**
	 * 
	 * @param string|Content[]|object $name
	 */
	public function getVariable($name) {
		return isset($this->vars[$name]) ? $this->vars[$name] : NULL;
	}
	
	/**
	 * @access protected
	 * This method is namespace protected
	 *
	 * @param string $loopName
	 * @param int $iteration
	 */
	public function loopMerge($loopName, $iteration) {
		$loop = $this->vars[$loopName];
		$result = clone $this;
		if (isset($result->vars[$loopName][$iteration]->vars[$loopName]))
			$result->vars[$loopName] = $result->vars[$loopName][$iteration]->vars[$loopName];
		else
			unset($result->vars[$loopName]);
		foreach($loop[$iteration]->vars as $key => $value)
			$result->vars[$loopName.'.'.$key] = $value;
		return $result;
	}
	
	/**
	 * 
	 * @param string $name
	 * @param Content $content
	 */
	public function putNamespace($name, $content) {
		if ($content instanceof Content)
			$this->namespaces[strtolower($name)] = $content;
		else
			user_error('$content should be of type Content');
	}
	
	public function getNamespace($name) {
		if (isset($this->namespaces[strtolower($name)]))
			return $this->namespaces[strtolower($name)];
		else
			return new Content();
	}
	
	/**
	 * Retrieves an array containing the names
	 * of the variables and loops contained in this
	 * Content.
	 * @return string[] The names of the variables and loops in this
	 * Content object
	 */
	public function getVariableNames(){
		return array_keys($this->vars);
	}
	
	public function jsonSerialize() {
		return $this->vars;
	}
	
}
