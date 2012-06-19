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
	private function __clone() {
		unset($this->blockKeywords, $this->annotations, $this->blocks, $this->variables);
	}
	
	/** @var Content */
	protected $content;
	/** @var object[] */
	protected $objects = array();
	
	/** @var string */
	protected $filename;
	
	/** @var string[] */
	private $tempVars;
	/** @var string[] */
	private $validPrefixes = array();
	
	private $extraReplacePattern = array();
	private $extraReplaceReplacement = array();
	
	const REGEX_BLOCK = '_\\<\\!\\-\\- ([a-z0-9\\_\\-\s]+)\s([a-z0-9\\.\\_\\-]+) \\-\\-\\>(.*?)\\<\\!\\-\\- /\\1 \\2 \\-\\-\\>_msi';
	const REGEX_ANNOTATION = '_\\<\\!\\-\\- ([a-z0-9\\.\\_\\-]+) ([a-z0-9\\.\\_\\-]+) \\-\\-\\>_i';
	const KEYWORD = 1;
	const BLOCKNAME = 2;
	const CONTENT = 3;
	
	const REGEX_VAR = '_\\{(#?[a-z0-9\\.\\_:]*?)\\}_i'; // {VAR} and {iteration.VAR}
	const VARNAME = 1;
	
	const REGEX_COMMENT = '_\\<\\!\\-\\- (.*) \\-\\-\\>_i';
	
	public static function templateFromFile($filename, $resourceName='resources') {
		if (!is_string($resourceName) || !$resourceName)
			$resourceName = 'resources';
		$result = new Template(file_get_contents($filename));
		$result->filename = $filename;
		$result->extraReplacePattern[] = '_(?<=["\'])'.$resourceName.'/(.*?)\\1_';
		$result->extraReplaceReplacement[] = Core::getUrlManager()->server['BASE_URL'].dirname($filename).'/'.$resourceName.'/';
		
		return $result;
	}
	
	/**
	 * 
	 * @param string $templateCode
	 */
	public function __construct($templateCode) {
		$this->templateCode = $templateCode;
	}
	
	/**
	 * @return string[]	a one-dimensional array containing all blocks
	 * 	as they appear in the template
	 * @todo namespace support
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
	 * @todo namespace support
	 */
	public function getVariables() {
		if (!is_null($this->variables))
			return $this->variables;
		preg_match_all(self::REGEX_VAR, $this->templateCode, $matches);
		return $this->variables = array_unique($matches[self::VARNAME]);
	}
	
	/**
	 * Set the content used by #render()
	 * 
	 * @param Content $content
	 */
	public function setContent($content) {
		$this->content = $content;
	}
	
	public function __toString() {
		$this->render();
	}
	
	public function getFilename() {
		return $this->filename;
	}
	
	/**
	 * 
	 */
	public function render() {
		$templateCode = $this->templateCode;
		$this->tempVars = array();
		$templateCode = preg_replace($this->extraReplacePattern, $this->extraReplaceReplacement, $templateCode);
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
		$namespacePath = preg_split('/[\s,]+/', $matches[self::KEYWORD]);
		$keyword = array_pop($namespacePath);
		$content = $this->content;
		foreach($namespacePath as $namespace) {
			$content = $content->getNamespace($namespace);
		}
		$tpl = clone $this;
		$tpl->templateCode = $matches[self::CONTENT];
		$tpl->content = $content;
		$function = 'render'.ucfirst(strtolower($keyword)).'Block';
		if (method_exists($tpl, $function)) {
			$this->tempVars[] = $tpl->$function($matches[self::BLOCKNAME]);
			return '{#'.(count($this->tempVars)-1).'}';
		} user_error('Invalid keyword: '.$keyword);
	}
	
	private function matchVar($matches) {
		if ($matches[self::VARNAME]{0} == '#')
			return $this->tempVars[substr($matches[self::VARNAME], 1)];
		$namespacePath = explode(':', $matches[self::VARNAME]);
		$varName = array_pop($namespacePath);
		$content = $this->content;
		foreach($namespacePath as $namespace)
			$content = $content->getNamespace($namespace);			
		$result = $content->getVariableString($varName);
		if (is_null($result)) {
			$result = $this->getValueFromObject($varName);
		}
		return $result;
	}
	
	protected function getValueFromObject($varName) {
		$result = NULL;
		$pos = strrpos($varName, '.');
		if (!$pos)
			return NULL;
		$objectKey = substr($varName, 0, $pos);
		$fieldKey = substr($varName, $pos+1);
		$method = 'get'.ucfirst($fieldKey);
		if (!isset($this->objects[$objectKey]) && strpos($objectKey, '.'))
			$this->objects[$objectKey] = $this->getValueFromObject($objectKey);
		if (isset($this->objects[$objectKey]) && isset($this->objects[$objectKey]->$fieldKey))
			$result = $this->objects[$objectKey]->$fieldKey;
		elseif (isset($this->objects[$objectKey]) && method_exists($this->objects[$objectKey], $method))
			$result = $this->objects[$objectKey]->$method();
		return $result;
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param string $blockName
	 */
	protected function renderForBlock($blockName) {
		$blocks = $this->content->getVariable($blockName);
		if (!$blocks) $blocks = $this->getValueFromObject($blockName);
		if (!$blocks) $blocks = array();
		$this->validPrefixes = array_merge($this->validPrefixes, array($blockName => $blockName));
		$result = '';
		if (is_array($blocks)) foreach($blocks as $iteration => $block) {
			$oldContent = $this->content;
			if (is_object($block) && $block instanceof Content) {
				$this->content = $this->content->loopMerge($blockName, $iteration);
			} elseif (is_object($block)) {
				$this->objects[$blockName] = $block;
			}
			$result .= $this->render();
			$this->content = $oldContent;
		}
		return $result;
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param string $block
	 */
	protected function renderIfBlock($block) {
		if ($var = $this->content->getVariable($block)) {
			if (is_array($var))
				$this->content->putVariables($var);
			elseif (is_object($var))
				$this->objects[$block] = $var;
			return $this->render();
		}
	}
	
	/**
	 *
	 * @param string $templateCode
	 * @param string $block
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	protected function renderIfnsBlock($block) {
		if ($this->content->getNamespace($block)) {
			return $this->render();
		}
	}
	
	/**
	 *
	 * @param string $templateCode
	 * @param string $block
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	protected function renderNsBlock($block) {
		if ($origContent = $this->content->getNamespace($block)) {
			$this->content = clone $origContent;
			$this->content->putNamespace('parent', $origContent);
			return $this->render();
		}
	}
	
	/**
	 * 
	 * @param string $templateCode
	 * @param string $block
	 * @param Content $content
	 * @param string[] $validPrefixes
	 */
	protected function renderNotBlock($block) {
		if (!($this->content->getVariable($block)))
			return $this->render();
	}
	
}
