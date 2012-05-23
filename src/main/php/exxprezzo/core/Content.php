<?php namespace exxprezzo\core;

class Content {

	/** @var string[] */
	protected $vars = array();
	/** @var Content[][] */
	protected $blocks = array();
	
	public function putVariable($key, $value) {
		$this->vars[$key] = $value;
	}
	public function putVariables($variables) {
		$this->vars = array_merge($this->vars, $variables);
	}
	
	/**
	 * 
	 * @param unknown_type $blockName
	 */
	protected function &getBlock($blockName) {
		$blocks = explode('.', $blockName);
		$last = array_pop($blocks);
		$root = &$this->blocks;
		foreach($blocks as $blockName) {
			$root = &$root[$blockName];
			$root = &$root[count($root)-1]->blocks;
		}
		return $root[$last];
	}
	
	/**
	 * 
	 * @param string $blockName
	 * @param string[]|Content $block
	 */
	public function addBlock($blockName, $block) {
		$parentBlock = &$this->getBlock($blockName);
		if (is_object($block) && $block instanceof Content) {
			$parentBlock[] = $block;
		} else if (is_array($block)) {
			$content = new Content();
			$content->vars = $block;
			$parentBlock[] = $content;
		} else {
			user_error('Invalid type for $block: '.gettype($block));
		}
		
	}
	/**
	 * 
	 * @param string $blockName
	 * @param string[] $variables
	 */
	public function appendBlock($blockName, $variables) {
		$parentBlock = &$this->getBlock($blockName);
		$parentBlock[count($parentBlock)-1]->vars
			= array_merge(
					$parentBlock[count($this->blocks)-1]->vars,
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
	 */
	public function getBlocks($name) {
		return $this->blocks[$name];
	}
	
	/**
	 * 
	 * @param string $blockName
	 * @param int $iteration
	 */
	public function merge($blockName, $iteration) {
		$result = clone $this;
		foreach($result->blocks[$blockName][$iteration]->vars as $key => $value)
			$result->vars[$blockName.'.'.$key] = $value;
		foreach($result->blocks[$blockName][$iteration]->blocks as $key => $value)
			$result->blocks[$blockName.'.'.$key] = $value;
		unset($result->blocks[$blockName]);
		return $result;
	}
	
}
