<?php namespace exxprezzo\core;

use \DateTime;
use \DateTimeZone;

class Content implements \JsonSerializable, \ArrayAccess, \IteratorAggregate {

	/** @var (string|\exxprezzo\core\Content[]|object)[] */
	protected $vars = array();
	/** @var Content[] */
	protected $namespaces = array();
	
	public function __construct($initialValue=NULL) {
		if (!is_null($initialValue))
			$this->putVariables($initialValue);
	}
	
	public function putVariable($key, $value) {
		if (is_array($value))
			$this->vars[$key] = new Content($value);
		else
			$this->vars[$key] = $value;
	}
	public function putVariables(array $variables) {
		foreach($variables as $key => $value)
			$this->putVariable($key, $value);
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
	
	public function putLoop($loopName, $loop) {
		$loops = explode('.', $loopName);
		$last = array_pop($loops);
		$root = &$this->vars;
		foreach($loops as $loopName) {
			$root = &$root[$loopName];
			$root = &$root[count($root)-1]->vars;
		}
		$root[$last] = $loop;
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
			$parentLoop[] = new Content($loop);
		} else {
			user_error('Invalid type for $loop: '.gettype($loop));
		}
		
	}
	
	/**
	 * 
	 * @param string $name
	 */
	public function getVariableString($name) {
		$var = $this->getVariable($name);
		if (is_object($var)) {
			if (method_exists($var, '__toString'))
				return $var->__toString();
			if ($var instanceof DateTime) {
				$var->setTimezone(new DateTimeZone(date_default_timezone_get()));
				return $var->format(DATE_RFC2822);
			}
			return 'Object';
		} elseif (is_string($var))
			return $var;
		elseif (is_null($var))
			return '';
		user_error('The variable {'.$name.'} is of type '.gettype($var).', must be string or object with __toString() method');
	}
	
	/**
	 * 
	 * @param string|Content[]|object $name
	 */
	public function getVariable($name) {
		return Core::resolve($this->vars, $name);
	}
	
	/**
	 * This method is namespace protected
	 * @access protected
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
			return null;
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
	
	public function offsetExists($offset) {
		return isset($this->vars[$offset]);
	}
	public function offsetGet($offset) {
		return $this->vars[$offset];
	}
	public function offsetSet($offset, $value) {
		$this->vars[$offset] = $value;
	}
	public function offsetUnset($offset) {
		unset($this->vars[$offset]);
	}
	public function getIterator() {
		return new ArrayObject($this->vars);
	}
	
	
}
