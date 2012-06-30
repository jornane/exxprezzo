<?php namespace exxprezzo\core\layout;

use \exxprezzo\core\Core;
use \exxprezzo\core\Content;
use \exxprezzo\core\Template;

use \exxprezzo\core\module\AbstractModule;

use \exxprezzo\core\output\AbstractOutput;
use \exxprezzo\core\output\BlockOutput;
use \exxprezzo\core\output\PartialOutput;

final class Page extends AbstractOutput {
	
	/** @var AbstractModule */
	private $mainModule;
	
	/** @var array */
	private $widgets = array();
	
	private $pageId;
	private $templateName;
	private $layout;
	
	/** @var Output	Output object to render a page for */
	private $main;
	
	/**
	 * 
	 * @param Output $output
	 */
	public static function supportsOutput($output) {
		return $output instanceof PartialOutput;
	}
	
	/**
	 * 
	 * @param Output $outputObject
	 */
	public function __construct($outputObject) {
		$this->main = $outputObject;
		$this->mainModule = $this->main->getSource();
		$dbh = Core::getDatabaseConnection();
		$dbh->execute('SELECT `pageId`, `preferredFunctionTemplate`, `theme`, `name`, `defaultBox` FROM `page`
				JOIN `layout` ON `layout`.`layoutId` = `page`.`layoutId`
				WHERE (`moduleInstanceId` = :moduleInstanceId OR `moduleInstanceId` IS NULL)
				AND (`function` = :function OR `function` IS NULL)
				ORDER BY (`moduleInstanceId`!="" AND `function`!=""), `moduleInstanceId`!=""
				LIMIT 1', array(
						'moduleInstanceId' => $this->mainModule->getInstanceId(),
						'function' => $this->mainModule->getFunctionName(),
					));
		if ($layoutEntry = $dbh->fetchrow()) {
			$this->pageId = $layoutEntry['pageId'];
			$this->templateName = $layoutEntry['preferredFunctionTemplate']
					? $layoutEntry['preferredFunctionTemplate']
					: $this->mainModule->getFunctionName()
				;
			$this->layout = array(
					'theme' => $layoutEntry['theme'],
					'name' => $layoutEntry['name'],
					'defaultBox' => $layoutEntry['defaultBox'],
				);
		} else {
			user_error("No layout found. Did you forget to specify a default layout?");
		}
		$dbh->execute('SELECT `widgetId`, `moduleInstanceId`, `function`, `preferredFunctionTemplate`, `box`, `param` FROM `widget`
				WHERE `pageId` = :pageId OR `pageId` IS NULL
				ORDER BY `priority` ASC', array(
						'pageId' => $this->pageId,
					));
		while($widget = $dbh->fetchrow()) {
			$this->widgets[$widget['box']][$widget['widgetId']] = $widget;
			$module = AbstractModule::getInstance($widget['moduleInstanceId']);
			$param = AbstractModule::parseParam($widget['param']);
			$this->widgets[$widget['box']][$widget['widgetId']]['module'] = $module;
			$this->widgets[$widget['box']][$widget['widgetId']]['output'] = $module->$widget['function']($param);
			$this->widgets[$widget['box']][$widget['widgetId']]['template'] = $widget['preferredFunctionTemplate']
					? $widget['preferredFunctionTemplate']
					: $widget['function']
				;
		}
	}
	
	public function getContent() {
		/** @var Content */
		$outputContent = new Content();
		/** @var AbstractModule */
		$module = $this->main->getSource();
		/** @var Output */
		$widgetOutput = $this->main;
		
		if ($widgetOutput instanceof BlockOutput)
			$widgetOutput->setTemplate(static::getTemplate(
					$module,
					$this->templateName,
					$this->layout['theme']
				));
		
		if (!$widgetOutput->getLength())
			user_error(
					'No output was generated by function "'
					.$module->getFunctionName().'" of module "'.$module.'"'
				);
		
		$template = static::getTemplate(
				$this,
				$this->layout['name'],
				$this->layout['theme']
			);
		
		if (!in_array($this->layout['defaultBox'], $template->getBlocks()))
			user_error(
					'The template "'.$this->layout['name']
					.'" does not contain the default box. ('.$this->layout['defaultBox'].')'
				);
		$outputContent->addLoop($this->layout['defaultBox'], array(
				'CONTENT' => $widgetOutput,
			));
		$outputContent->putVariable('TITLE', $module->getTitle($module->getModuleParam()));
		
		
		foreach($this->widgets as $box => $widgets) foreach($widgets as $widget) {
			if ($widget['output'] instanceof BlockOutput)
				$widget['output']->setTemplate(static::getTemplate(
						$widget['module'],
						$widget['template'],
						$this->layout['theme']
					));
			$outputContent->addLoop($box, array(
					'CONTENT' => $widget['output'],
				));
		}
		$template->setContent($outputContent);
		return $template->render();
	}
	
	public function getContentType() {
		return $this->main->getContentType();
	}
	
	/**
	 * 
	 * @param object $object
	 * @param string $templateName
	 * @param string $themeName
	 */
	public static function getTemplate($object, $templateName, $themeName) {
		assert('is_object($object);');
		assert('is_string($templateName);');
		assert('is_string($themeName);');
		
		$fqn = get_class($object);
		$fqnSplit = explode('\\', $fqn);
		$simpleName = array_pop($fqnSplit);
		$namespace = $fqnSplit;
		
		if (array_shift($fqnSplit) != 'exxprezzo')
			user_error('$object must be from a class in the exxprezzo namespace');
		if (reset($fqnSplit) == 'core')
			array_shift($fqnSplit);
		$kind = array_shift($fqnSplit);
		
		$pathOptions = array(
				'template' . DIRECTORY_SEPARATOR
					. $themeName . DIRECTORY_SEPARATOR
					. $kind . DIRECTORY_SEPARATOR
					. reset($fqnSplit) . DIRECTORY_SEPARATOR
					. $templateName . '.tpl',
				implode(DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR
					. 'template' . DIRECTORY_SEPARATOR
					. $templateName . '.tpl',
			);
		foreach($pathOptions as $pathOption)
			if (is_readable($pathOption))
				return Template::templateFromFile($pathOption);
		user_error('A template could not be found on any of the following locations: \''.implode('\', \'', $pathOptions)).'\'';
	}
	
}
