<?php namespace exxprezzo\module\cms;

use exxprezzo\core\Core;

use exxprezzo\core\Content;

use exxprezzo\core\output\ContentOutput;

use exxprezzo\core\module\AbstractModule;

class CMS extends AbstractModule {
	
	protected $db;
	
	protected static $functions = array(
			'(?<path>.*)/' => 'view',
			'(?<path>.*)/edit.html' => 'edit',
	);
	
	public function getTitle() {
		$db = $this->getModuleParam();
		$params = $this->getParameters();
		$db->execute('SELECT `title`, `content` FROM `pages` WHERE `path` = $path LIMIT 1', array(
				'path' => ltrim($params['path'], '/'),
			));
		if($page = $db->fetchrow())
			return $page['title'];
	}
		
	public function view() {
		$db = $this->getModuleParam();
		$content = new Content();
		$params = $this->getParameters();
		$db->execute('SELECT `title`, `content` FROM `pages` WHERE `path` = $path LIMIT 1', array(
				'path' => ltrim($params['path'], '/'),
			));
		if($page = $db->fetchrow()) {
			$content->putVariables(array(
					'CONTENT' => $page['content'],
				));
		} else {
			user_error('Page not found');
		}
		return new ContentOutput($this, $content);
	}
	
	public function edit() {
		
	}
	
}
