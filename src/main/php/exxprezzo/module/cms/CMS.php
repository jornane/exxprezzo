<?php namespace exxprezzo\module\cms;

use \exxprezzo\core\input\FileInput;

use \exxprezzo\module\filemanager\FileManager;
use \exxprezzo\module\filemanager\File;

use \exxprezzo\core\output\PostOutput;

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
	
	/**	@var \exxprezzo\core\db\SQL */
	protected $db;
	/** @var string[] */
	protected $params;
	
	protected static $functions = array(
			'(?<path>.*)/' => 'view',
			'(?<path>.*)/edit.html' => 'edit',
			'(?<path>.*)/edit.cgi' => 'doEdit',
			'(?<path>.*)/files.html' => 'files',
			'(?<path>.*)/files.cgi' => 'editFiles',
			'(?<path>.*)/images.html' => 'images',
			'(?<path>.*)/(?<filename>[^/]+\.[a-zA-Z0-9]+)' => 'file',
		);
	protected static $paths = array(
			'view' => array('{$path}/'),
			'edit' => array('{$path}/edit.html'),
			'doEdit' => array('{$path}/edit.cgi'),
			'files' => array('{$path}/files.html'),
			'editFiles' => array('{$path}/files.cgi'),
			'images' => array('{$path}/images.html'),
			'file' => array('{$path}/{$filename}'),
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
		static::$pages[$path] = $this->db->fetchrow();
		if (static::$pages[$path]) {
			static::$pages[$path]['rawcontent'] = static::$pages[$path]['content'];
			static::$pages[$path]['content'] = preg_replace(
					'_(?<=["\'])././(.*?)\\1_',
					$this->mkurl('view'),
					static::$pages[$path]['content']
				);
		}
		return static::$pages[$path];
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
		} else if ($canEdit)
			$this->redirect('edit');
		else
			user_error('Page not found.');
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
					'title' => new TextInput('title', $page['title']),
					'content' => new LongTextInput('content', $page['rawcontent']),
				));
			if ($this->params['path'])
				$input->putVariable('delete', new ButtonInput('delete', 'Delete'));
		} else {
			$input->putVariables(array(
					'title' => new TextInput('title', 'New page'),
					'content' => new LongTextInput('content', 'Lorem ipsum dolor...'),
				));
		}
		return new PostOutput(new BlockOutput($this, $content), $this->mkurl('doEdit'));
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
			$this->redirect('view', array('path' => dirname($this->params['path'])));
		}
		$this->redirect('view');
	}
	
	public function editFiles($params, $content) {
		/** @var \exxprezzo\module\filemanager\File */
		$file = FileManager::storeUploadFile($this, 'file');
		$this->db->replace('files', array(
				'path' => ltrim($params['path'], '/'),
				'filename' => $file->getFilename(),
				'file' => $file->getFileId(),
			));
		$this->redirect('files');
	}
	
	public function files($params, $void, $imagesOnly=false) {
		$content = new Content();
		$input = new Content();
		$content->putNamespace('input', $input);
		
		$page = $this->fetchPage($this->params['path']);
		$this->db->execute('SELECT `filename`, `file` FROM `files` WHERE `path` = $path', array(
				'path' => ltrim($params['path'], '/'),
			));
		$fileEntries = array();
		while($entry = $this->db->fetchrow())
			$fileEntries[$entry['filename']] = $entry['file'];
		$files = File::getLoadedInstances($this, $fileEntries);
		foreach($files as $file) {
			// if image
			//TEMPORARY:
			$file->href = $this->mkurl('file', array('filename'=>$file->getFilename()));
			$content->addLoop('file', $file);
		}
		$input->putVariables(array(
				'file' => new FileInput('file'),
			));
		return new PostOutput(new BlockOutput($this, $content), $this->mkurl('editFiles'), true);
	}
	
	public function images($params, $void) {
		return $this->files($params, $void, true);
	}
	
	public function file($params) {
		$this->db->execute('SELECT `file` FROM `files` WHERE `path` = $path AND `filename` = $filename', array(
				'path' => ltrim($params['path'], '/'),
				'filename' => $params['filename'],
			));
		if ($entry = $this->db->fetchrow()) {
			$file = new File($this, $entry['file']);
			$file->passthru();
			exit;
		}
		user_error('File not found'); // Make me a 404
	}
	
}
