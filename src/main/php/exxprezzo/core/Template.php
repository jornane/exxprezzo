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
	
	protected $content;
	
	const REGEX_BLOCK = '_\\<\\!\\-\\- ([A-Z0-9\\_\\-]+) ([a-z0-9\\.\\_\\-]+) \\-\\-\\>(.*?)\\<\\!\\-\\- /\\1 \\2 \\-\\-\\>_ms';
	const REGEX_ANNOTATION = '_\\<\\!\\-\\- ([a-z0-9\\.\\_\\-]+) ([a-z0-9\\.\\_\\-]+) \\-\\-\\>_i';
	const KEYWORD = 1;
	const BLOCKNAME = 2;
	const CONTENT = 3;
	
	const REGEX_VAR = '_\\{([a-z0-9#\\.\\_\\-]*?)\\}_i'; // {VAR} and {iteration.VAR}
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
	
	public function setContent($content) {
		$this->content = $content;
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	public function render($templateCode=NULL, $content=NULL, $validPrefixes=array()) {
		if (is_null($templateCode))
			$templateCode = $this->templateCode;
		if (is_null($content))
			$content = $this->content;
		$tempVars = array();
		$templateCode = preg_replace_callback(self::REGEX_BLOCK, function ($matches) {
				$function = array($this, 'render'.ucfirst(strtolower($matches[self::KEYWORD])).'Block');
				if (function_exists($function)) {
					$tempVars[] = $function($matches[self::CONTENT], $matches[self::BLOCKNAME], $validPrefixes);
					return '{#'.(count($tempVars)-1).'}';
				} return '';
			}, $templateCode);
		$templateCode = preg_replace(self::REGEX_ANNOTATION, '', $templateCode);
		$templateCode = preg_replace_callback(self::REGEX_VAR, function ($matches) {
				return $matches{0} == '#'
						? $tempVars[substr($matches[self::VARNAME], 1)]
						: $content->getVariable($matches[self::VARNAME])
					;
			}, $templateCode);
		return $templateCode;
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param string $blockName
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	protected function renderForBlock($templateCode, $blockName, $content, $validPrefixes) {
		$blocknamesplit = explode('.', $blockName);
		$blockDepth = sizeof($blocknamesplit)-1;
		for ($i=0; $i<$blockDepth; $i++) {
			$currentprefix .= $blocknamesplit[$i];
			if (!isset($prefixes[$currentprefix])) { // Invalid blockname; no parent block found
				Core::debug(new ConstraintsException('Trying to render block "'.$blockname.'" while block "'.$currentprefix.'" does not exist'));
				return false;
			}
			$block = &$block[$blocknamesplit[$i]][$prefixes[$currentprefix]]['blocks'];
				
			$currentprefix .= '.';
		}
		$currentprefix .= $blocknamesplit[$blocksize].'.';
		$block = &$block[$blocknamesplit[$blocksize]];
		$result = '';
		if (is_array($block)) foreach($block as $number => $blockvars) {
			foreach($blockvars['values'] as $key => $value) {
				unset($blockvars['values'][$key]);
				$blockvars['values'][$currentprefix.$key] = $value;
			}
			$newContent = clone $content;
			$content->putVariables($blockvars['values']);
			$result .= $this->render($templatecode, array_merge($vars, $blockvars['values']), $blocks, array_merge($prefixes, array($blockname => $number)), $files, $workdir, true);
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
	protected function renderIfBlock($templateCode, $block, $content, $validPrefixes) {
		if (empty($content->getBlock($block)))
			return render($templateCode, $content, $validPrefixes);
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param string $block
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	protected function renderNotBlock($templateCode, $block, $content, $validPrefixes) {
		if (!empty($content->getBlock($block)))
			return render($templateCode, $content, $validPrefixes);
	}
	
}
