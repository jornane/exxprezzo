<?php namespace exxprezzo\core;

class Template {
	
	/** @var string */
	protected $templateCode;
	/** @var string[] */
	protected $blockKeywords;
	
	/** @var string[] */
	protected $annotations;
	/** @var string[] */
	protected $blocks;
	/** @var string[] */
	protected $variables;
	
	/** @var Content */
	protected $content;
	
	/** @var string[] */
	private $tempVars;
	/** @var string[] */
	private $validPrefixes = array();
	
	private function __clone() {
		unset($this->tempVars);
	}
	
	const REGEX_BLOCK = '_\\<\\!\\-\\- ([A-Z0-9\\_\\-]+) ([a-z0-9\\.\\_\\-]+) \\-\\-\\>(.*?)\\<\\!\\-\\- /\\1 \\2 \\-\\-\\>_ms';
	const REGEX_ANNOTATION = '_\\<\\!\\-\\- ([a-z0-9\\.\\_\\-]+) ([a-z0-9\\.\\_\\-]+) \\-\\-\\>_i';
	const KEYWORD = 1;
	const BLOCKNAME = 2;
	const CONTENT = 3;
	
	const REGEX_VAR = '_\\{([a-z0-9#\\.\\_\\-]*?)\\}_i'; // {VAR} and {iteration.VAR}
	const VARNAME = 1;
	
	const REGEX_COMMENT = '_\\<\\!\\-\\- (.*) \\-\\-\\>_i';
	
	/**
	 * 
	 * @param string $templateCode
	 */
	public function __construct($templateCode) {
		$this->templateCode = $templateCode;
	}
	
	/**
	 * @return string[]	a one-dimensional array containing all blocks
	 * 	as they appear in the template (dot separated)
	 */
	public function getBlocks() {
		if (!is_null($this->blocks))
			return $this->blocks;
		preg_match_all(self::REGEX_BLOCK, $this->templateCode, $matches);
		if (is_null($this->blockKeywords))
			$this->blockKeywords = array_unique($matches[self::KEYWORD]);
		return $this->blocks = array_unique($matches[self::BLOCKNAME]);
	}
	
	/**
	 * @return string[] an indexed array containing all annotations
	 * 	as they appear in the template (key => value)
	 */
	public function getAnnotations() {
		if (!is_null($this->annotations))
			return $this->annotations;
		if (is_null($this->blockKeywords))
			$this->getBlocks();
		preg_match_all(self::REGEX_ANNOTATION, $this->templateCode, $matches);
		$result = array_combine($matches[self::KEYWORD], $matches[self::BLOCKNAME]);
		foreach($this->blockKeywords as $key)
			unset($result[$key]);
		return $this->annotations = $result;
	}
	
	/**
	 * @return string[] a list containing all variables as they appear in the template
	 */
	public function getVariables() {
		if (!is_null($this->variables))
			return $this->variables;
		preg_match_all(self::REGEX_VAR, $this->templateCode, $matches);
		return $this->variables = array_unique($matches[self::VARNAME]);
	}
	
	/**
	 * Set the content used by #render($templateCode)
	 * 
	 * @param Content $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	public function render($templateCode=NULL) {
		if (is_null($templateCode))
			$templateCode = $this->templateCode;
		$this->tempVars = array();
		$templateCode = preg_replace_callback(self::REGEX_BLOCK, array($this, 'matchBlock'), $templateCode);
		$templateCode = preg_replace(self::REGEX_ANNOTATION, '', $templateCode);
		$templateCode = preg_replace_callback(self::REGEX_VAR, array($this, 'matchVar'), $templateCode);
		$templateCode = preg_replace('_([[:blank:]]{2,})_', ' ', $templateCode);
		$templateCode = preg_replace('_([\s]{2,})_', "\n", $templateCode);
		return $templateCode;
	}
	
	/**
	 * 
	 * @param string[][] $matches
	 * @return string
	 */
	private function matchBlock($matches) {
		$tpl = clone $this;
		$function = 'render'.ucfirst(strtolower($matches[self::KEYWORD])).'Block';
		if (method_exists($tpl, $function)) {
			$this->tempVars[] = $tpl->$function($matches[self::CONTENT], $matches[self::BLOCKNAME]);
			return '{#'.(count($this->tempVars)-1).'}';
		} return 'ERR';
	}
	
	private function matchVar($matches) {
		return $matches[self::VARNAME]{0} == '#'
				? $this->tempVars[substr($matches[self::VARNAME], 1)]
				: $this->content->getVariableString($matches[self::VARNAME])
			;
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param string $blockName
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	protected function renderForBlock($templateCode, $blockName) {
		$blocks = $this->content->getBlocks($blockName);
		if (!$blocks) $blocks = array();
		$this->validPrefixes = array_merge($this->validPrefixes, array($blockName => $blockName));
		$result = '';
		foreach($blocks as $iteration => $block) {
			$oldContent = $this->content;
			$this->content = $this->content->merge($blockName, $iteration);
			$result .= $this->render($templateCode);
			$this->content = $oldContent;
		}
		return $result;
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param string $block
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	protected function renderIfBlock($templateCode, $block) {
		if ($this->content->getBlocks($block))
			return $this->render($templateCode, $this->content);
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param string $block
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	protected function renderNotBlock($templateCode, $block) {
		if (!($this->content->getBlocks($block)))
			return $this->render($templateCode, $this->content);
	}
	
}
