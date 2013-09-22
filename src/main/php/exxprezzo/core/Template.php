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

	/** 
	 * Raw recursive key-value content
	 * @var \exxprezzo\core\Content 
	 */
	protected $content;

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

	/**
	 * Instantiate a new template from a file
	 * This method is recommended over constructing it yourself,
	 * because it will set the location of the template file,
	 * allowing for relative links in the template file.
	 */
	public static function templateFromFile($filename, $resourceName='./.') {
		if (!is_string($resourceName) || !$resourceName)
			$resourceName = 'resources';
		$template = new Template(file_get_contents($filename));
		$template->filename = $filename;

		$template->registerLocalResourcePath($filename, $resourceName);

		return $template;
	}

	/**
	 * Add a custom replacement which will allow pointing to local 
	 * files by prepending them with the resourceName.
	 * The standard resourceName is ./. so you can prepend ././ to a path 
	 * to make it resolve to a file local to the template.
	 */
	private function registerLocalResourcePath($filename, $resourceName) {
		$this->extraReplacePattern[] = '_(?<=["\'])'.$resourceName.'/(.*?)\\1_';
		$filenameSplice = explode('/', $filename);
		$this->extraReplaceReplacement[] = Core::getUrlManager()->getBaseUrl() .
			implode('/', array_slice($filenameSplice, 0, array_search('template', $filenameSplice)+2)).'/';
	}

	/**
	 *
	 * @param string $templateCode
	 */
	public function __construct($templateCode) {
		$this->templateCode = $templateCode;
	}

	/**
	 * Get all blocks in this template
	 *
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
	 * Get all annotations in this template
	 *
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
	 * Get all variables in this template
	 *
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
		assert('is_object($this->content)');
		assert('$this->content instanceof \\exxprezzo\\core\\Content');
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
	 * Replace a block with its template output.
	 * 
	 * @param	string[][]	$matches
	 * @return	string
	 */
	private function matchBlock($matches) {
		$namespacePath = preg_split('/[\s,]+/', $matches[self::KEYWORD]);
		$keyword = array_pop($namespacePath);
		
		// Push current content object, as we're going to change it.
		$content = $this->content;
		
		// Resolve the namespace
		foreach($namespacePath as $namespace) {
			$content = $content->getNamespace($namespace);
			if (is_null($content))
				user_error('Invalid namespace: '.implode(':', $namespacePath).($this->filename?' (file '.$this->filename.')':''));
		}

		// Clone the current object to use as a workspace for parsing
		$tpl = clone $this;
		$tpl->templateCode = $matches[self::CONTENT];
		$tpl->content = $content;
		
		// Call block-specific render functions
		$function = 'render'.ucfirst(strtolower($keyword)).'Block';
		if (method_exists($tpl, $function)) {
			$this->tempVars[] = $tpl->$function($matches[self::BLOCKNAME]);
			return '{#'.(count($this->tempVars)-1).'}';
		} user_error('Invalid keyword: '.$keyword);
	}

	/**
	 * Replace a variable with the corresponding
	 * value from the $this->content object.
	 * 
	 * @param	string[][]	$matches
	 * @return	string
	 */
	private function matchVar($matches) {
		if ($matches[self::VARNAME]{0} == '#')
			return $this->tempVars[substr($matches[self::VARNAME], 1)];
		$namespacePath = explode(':', $matches[self::VARNAME]);
		$varName = array_pop($namespacePath);
		$content = $this->content;
		foreach($namespacePath as $namespace)
			$content = $content->getNamespace($namespace);

		return $content->getHTMLSafeString($varName);
	}

	/**
	 * Render a for-block by iterating over all elements 
	 * and rendering the block for each of them, 
	 * returning all results contactinated.
	 *
	 * @param	string	$blockName
	 * @result 	string
	 */
	protected function renderForBlock($blockName) {
		$blocks = $this->content->getVariable($blockName);
		$this->validPrefixes = array_merge($this->validPrefixes, array($blockName => $blockName));
		$result = '';
		$oldContent = $this->content;

		if (is_array($blocks)) foreach($blocks as $iteration => $block) {
			$oldVar = $this->content->getVariable($blockName);
			if (is_object($block) || is_array($block)) {
				if (!($block instanceof Content))
					$block = new Content($block);
				$this->content = $this->content->loopMerge($blockName, $iteration);
				$this->content->putVariable($blockName.'.RECURSE', $this->renderForBlock($blockName));
				$this->content->putVariable($blockName, $oldVar);
			}
			$result .= $this->render();

		}
		$this->content = $oldContent;
		return $result;
	}

	/**
	 * Only display the contents of this block when 
	 * the value of $variable resolves to true according to PHP.
	 *
	 * @see Template::renderNotBlock($block)
	 * @param string $variableName
	 */
	protected function renderIfBlock($variableName) {
		$var = $this->content->getVariable($variableName);
		if (!is_null($var)) {
			if (is_array($var))
				$this->content->putVariables($var);
			return $this->render();
		}
	}

	/**
	 * Only display the contents of this block
	 * when the named namespace exists.
	 *
	 * @param string $block
	 */
	protected function renderIfnsBlock($namespace) {
		if ($this->content->getNamespace($namespace)) {
			return $this->render();
		}
	}

	/**
	 * Switch the namespace context to the named namespace and render the block using this context.
	 * The original namespace will be available as namespace "parent".
	 *
	 * @param string $namespace
	 */
	protected function renderNsBlock($namespace) {
		if ($newContent = $this->content->getNamespace($namespace)) {
			$origContent = $this->content;
			$this->content = clone $newContent;
			$this->content->putNamespace('parent', $origContent);
			return $this->render();
		}
	}

	/**
	 * Only display the contents of this block when 
	 * the value of $variable resolves to false according to PHP.
	 *
	 * @see Template::renderIfBlock($block)
	 * @param string $variableName
	 */
	protected function renderNotBlock($variableName) {
		if (!($this->content->getVariable($variableName)))
			return $this->render();
	}

}
