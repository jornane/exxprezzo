<?php namespace exxprezzo\module\cms;

use \exxprezzo\core\db\SQL;

use \exxprezzo\core\input\ButtonInput;
use \exxprezzo\core\input\LongTextInput;
use \exxprezzo\core\input\TextInput;

use \exxprezzo\core\Core;
use \exxprezzo\core\Content;

use \exxprezzo\core\output\BlockOutput;

use \exxprezzo\core\module\AbstractModule;

class CMS extends AbstractModule {
	
	/** @var string[][] */
	protected static $pages;
	
	/**	@var SQL */
	protected $db;
	/** @var string[] */
	protected $params;
	
	protected static $functions = array(
			'(?<path>.*)/' => 'view',
			'(?<path>.*)/edit.html' => 'edit',
			'(?<path>.*)/edit.cgi' => 'doEdit',
	);
	protected static $paths = array(
			'view' => array('{$path}/'),
			'edit' => array('{$path}/edit.html'),
			'doEdit' => array('{$path}/edit.cgi'),
	);
	
	public function init() {
		parent::init();
		$this->db = $this->getModuleParam();
		$this->params = $this->getParameters();
	}
	
	public function fetchPage($path=NULL) {
		if (is_null($path))
			$path = $this->params['path'];
		if (isset(static::$pages[$path]))
			return static::$pages[$path];
		$this->db->execute('SELECT `title`, `content` FROM `pages` WHERE `path` = $path LIMIT 1', array(
				'path' => ltrim($path, '/'),
			));
		return static::$pages[$path] = $this->db->fetchrow();
	}
	
	public function getTitle($params) {
		if($page = $this->fetchPage())
			return $page['title'];
	}
	
	public function view($params) {
		$canEdit = true; // check permissions here
		$content = new Content();
		if($page = $this->fetchPage()) {
			$content->putVariables($page);
			if ($canEdit)
				$content->putVariable('editHref', $this->mkurl('edit'));
			return new BlockOutput($this, $content);
		} else if ($canEdit) {
			$this->redirect('edit');
		} else {
			user_error('Page not found.');
		}
	}
	
	public function edit($params) {
		// check permissions here
		$content = new Content();
		$input = new Content();
		$content->putNamespace('input', $input);
		if($page = $this->fetchPage($this->params['path'])) {
			$content->putVariables($page);
			$content->putVariable('exists', true);
			$input->putVariables(array(
					'formaction' => $this->mkurl('doEdit'),
					'formmethod' => 'post',
					'title' => new TextInput('title', $page['title']),
					'content' => new LongTextInput('content', $page['content']),
			));
			if ($this->params['path'])
				$input->putVariable('delete', new ButtonInput('delete', 'Delete'));
		} else {
			$input->putVariables(array(
					'formaction' => $this->mkurl('doEdit'),
					'formmethod' => 'post',
					'title' => new TextInput('title', 'New page'),
					'content' => new LongTextInput('content', 'Lorem ipsum dolor...'),
			));
		}
		return new BlockOutput($this, $content);
	}
	
	/**
	 * 
	 * @param Content $content
	 */
	public function doEdit($params, $content) {
		// check permissions here
		if ($content->getVariable('title') && $content->getVariable('content') && !$content->getVariable('delete')) {
			$this->db->replace('pages', array(
					'path' => ltrim($this->params['path'], '/'),
					'title' => $content->getVariable('title'),
					'content' => $content->getVariable('content'),
				));
		} elseif ($content->getVariable('delete')) {
			$path = ltrim($this->params['path'], '/');
			$this->db->delete('pages', array(
					'path' => $path,
				));
			$path = explode('/', $path);
			array_pop($path);
			$this->redirect('view', array('path' => implode('/', $path)));
		}
		$this->redirect('view');
	}
	
}
