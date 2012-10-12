<?php namespace exxprezzo\core;

use \DateTime;
use \DateTimeZone;

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
	
	/** @var \exxprezzo\core\Content */
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
	
	public static function templateFromFile($filename, $resourceName='./.') {
		if (!is_string($resourceName) || !$resourceName)
			$resourceName = 'resources';
		$result = new Template(file_get_contents($filename));
		$result->filename = $filename;
		$result->extraReplacePattern[] = '_(?<=["\'])'.$resourceName.'/(.*?)\\1_';
		$result->extraReplaceReplacement[] = Core::getUrlManager()->getBaseUrl().dirname($filename).'/';
		
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
		$result = $this->render();
		return $result;
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
		if (is_null($result))
			$result = $this->getValueFromObject($varName);
		if (is_object($result)) {
			if ($result instanceof DateTime) {
				$result->setTimezone(new DateTimeZone(date_default_timezone_get()));
				$result = $result->format(DATE_RFC2822);
			} else
				$result = $result->__toString();
		}
		return $result;
	}
	
	protected function getValueFromObject($varName) {
		return Core::resolve($this->objects, $varName);
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
			$oldObjects = $this->objects;
			$oldVar = $this->content->getVariable($blockName);
			
			if (is_object($block) && $block instanceof Content) {
				$this->content = $this->content->loopMerge($blockName, $iteration);
				unset($this->objects[$blockName]);
				$this->content->putVariable($blockName.'.RECURSE', $this->renderForBlock($blockName));
				$this->content->putVariable($blockName, $oldVar);
			} elseif (is_object($block)) {
				/*$this->content->removeVariable($blockName);
				foreach(array_keys($this->objects) as $key) {
					if (substr($key, 0, $blockName+1) == $blockName+'.') {
						unset($this->objects[$key]);
					}
				}*/
				$this->objects[$blockName] = $block; 
			}
			$result .= $this->render();
				
			$this->objects = $oldObjects;
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
		$var = $this->content->getVariable($block);
		if (is_null($var))
			$var = $this->getValueFromObject($block);
		if (!is_null($var)) {
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
		if ($newContent = $this->content->getNamespace($block)) {
			$origContent = $this->content;
			$this->content = clone $newContent;
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
