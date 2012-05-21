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
	
	const REGEX_BLOCK = '_\\<\\!\\-\\- ([A-Z0-9\\_\\-]+) ([a-z0-9\\.\\_\\-]+) \\-\\-\\>(.*?)\\<\\!\\-\\- /\\1 \\2 \\-\\-\\>_ms';
	const REGEX_ANNOTATION = '_\\<\\!\\-\\- ([a-z0-9\\.\\_\\-]+) ([a-z0-9\\.\\_\\-]+) \\-\\-\\>_i';
	const KEYWORD = 1;
	const BLOCKNAME = 2;
	
	const REGEX_VAR = '_\\{([a-z0-9\\.\\_\\-]*?)\\}_i'; // {VAR} and {iteration.VAR}
	const VARNAME = 1;
	
	const REGEX_COMMENT = '_\\<\\!\\-\\- (.*) \\-\\-\\>_i';
	
	public function __construct($templateCode) {
		$this->templateCode = $templateCode;
	}
	
	public function getBlocks() {
		if (!is_null($this->blocks))
			return $this->blocks;
		preg_match_all(self::REGEX_BLOCK, $this->templateCode, $matches);
		if (is_null($this->blockKeywords))
			$this->blockKeywords = array_unique($matches[self::KEYWORD]);
		return $this->blocks = array_unique($matches[self::BLOCKNAME]);
	}
	
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
	
	public function getVariables() {
		if (!is_null($this->variables))
			return $this->variables;
		preg_match_all(self::REGEX_VAR, $this->templateCode, $matches);
		return $this->variables = array_unique($matches[self::VARNAME]);
	}
	
	public function render($content) {
		// TODO
	}
	
}
