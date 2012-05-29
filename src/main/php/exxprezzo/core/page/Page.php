<?php namespace exxprezzo\core;

use exxprezzo\core\module\AbstractModule;

final class Page implements Output {
	
	/** @var Output */
	private $main;
	
	/** @var array */
	private $widgets = array();
	
	/**
	 * 
	 * @param Output $outputObject
	 */
	public function __construct($outputObject) {
		$this->main = $outputObject;
		$dbh = Core::getDatabaseConnection();
		$stmt = $dbh->prepare('SELECT `pageId`, `preferredFunctionTemplate`, `name`, `defaultBox` FROM `page`
				JOIN `layout` ON `layout`.`layoutId` = `page`.`layoutId`
				WHERE (`moduleInstanceId` = :moduleInstanceId OR `moduleInstanceId` IS NULL)
				AND (`function` = :function OR `function` IS NULL)
				ORDER BY (`moduleInstanceId`!="" AND `function`!=""), `moduleInstanceId`!=""
				LIMIT 1');
		$stmt->bindParam(':moduleInstanceId', $this->main->getSource()->getInstanceId());
		$stmt->bindParam(':function', $this->main->getSource()->getFunctionName());
		if ($stmt->execute() && $layoutEntry = $stmt->fetch()) {
			$this->pageId = $layoutEntry['pageId'];
			$this->templateName = $layoutEntry['preferredFunctionTemplate'];
			$this->layout = array(
					'name' => $layoutEntry['pageId'],
					'defaultBox' => $layoutEntry['defaultBox'],
				);
		} else {
			user_error("No layout found. Did you forget to specify a default layout?");
		}
		$stmt = $dbh->prepare('SELECT `widgetId`, `moduleInstanceId`, `function`, `preferredFunctionTemplate`, `box`, `param` FROM `widget`
				WHERE `pageId` = :pageId
				ORDER BY `priority` ASC');
		$stmt->bindParam(':moduleInstanceId', $this->main->getSource()->getInstanceId());
		if ($stmt->execute()) while($widget = $stmt->fetch()) {
			$this->widgets[$widget['box']][$widget['widgetId']] = $widget;
			$module = AbstractModule::getInstance($widget['moduleInstanceId']);
			$this->widgets[$widget['box']][$widget['widgetId']]['module'] = $module;
			$this->widgets[$widget['box']][$widget['widgetId']]['output'] = $module->$widget['function']($widget['param']);
		}
	}
	
	public function run() {
		$this->outputHeaders();
		$this->outputContent();
	}
	
	public function outputHeaders() {
		
	}
	
	public function outputContent() {
		
	}
	
}
