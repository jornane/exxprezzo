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
	 * @param string $blockName
	 * @param string[]|Content $block
	 */
	public function addBlock($blockName, $block) {
		$blocks = explode('.', $blockName);
		$last = array_pop($blocks);
		$root = &$this->blocks;
		foreach($blocks as $blockName) {
			$root = &$root[$blockName];
			$root = &$root[count($root)-1]->blocks;
		}
		$root = &$root[$last];
		if (is_object($block) && $block instanceof Content) {
			$root[] = $block;
		} else if (is_array($block)) {
			$content = new Content();
			$content->vars = $block;
			$root[] = $content;
		} else {
			user_error('Invalid type for $block: '.gettype($block));
		}
		
	}
	/**
	 * 
	 * @param unknown_type $blockName
	 * @param unknown_type $variables
	 */
	public function appendBlocks($blockName, $variables) {
		$this->blocks[$blockName][count($this->blocks)-1]->vars
			= array_merge(
					$this->blocks[$blockName][count($this->blocks)-1]->vars,
					$variables
				);
	}
	
	public function getVariable($name) {
		return isset($this->vars[$name]) ? $this->vars[$name] : NULL;
	}
	
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