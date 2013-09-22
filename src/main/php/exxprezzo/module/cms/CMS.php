<?php namespace exxprezzo\module\cms;

use \exxprezzo\core\input\FileInput;

use \exxprezzo\core\output\PostOutput;

use \exxprezzo\core\db\SQL;

use \exxprezzo\core\input\ButtonInput;
use \exxprezzo\core\input\LongTextInput;
use \exxprezzo\core\input\TextInput;

use \exxprezzo\core\Core;
use \exxprezzo\core\Content;

use \exxprezzo\core\exception\ItemNotFoundException;

use \exxprezzo\core\output\BlockOutput;

use \exxprezzo\core\module\AbstractModule;

use \exxprezzo\module\filemanager\FileManager;
use \exxprezzo\module\filemanager\File;
use \exxprezzo\module\acl\ACL;

use \exxprezzo\core\type\TrustedHtml;

class CMS extends AbstractModule {

	/** @var string[][] */
	protected static $pages;

	/**	@var \exxprezzo\core\db\SQL */
	protected $db;
	/** @var string[] */
	protected $params;

	protected static $functions = array(
			'(?<path>.*)/edit.cgi' => 'doEdit',
			'(?<path>.*)/edit.(?<ext>[a-z0-9]+)' => 'edit',
			'(?<path>.*)/images.(?<ext>[a-z0-9]+)' => 'images',
			'(?<path>.*)/files.cgi' => 'editFiles',
			'(?<path>.*)/files.(?<ext>[a-z0-9]+)' => 'files',
			'(?<path>.*)/(?<filename>[^/]+\.[a-zA-Z0-9]+)' => 'file',
			'(?<path>.*)/(?<ext>[a-z0-9]*)' => 'view',
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
		$this->db = $this->getModuleParam('db');
		$this->params = $this->getPathParameters();
	}

	public function fetchPage($path=NULL) {
		ACL::required('view', $this, true, $this->params);
		if (is_null($path))
			$path = $this->params['path'];
		if (isset(static::$pages[$path]))
			return static::$pages[$path];
		$this->db->execute('SELECT `title`, `content` FROM `pages` WHERE `path` = $path LIMIT 1', array(
				'path' => ltrim($path, '/'),
			));
		static::$pages[$path] = $this->db->fetchrow();
		if (static::$pages[$path]) {
			static::$pages[$path]['rawContent'] = static::$pages[$path]['content'];
			static::$pages[$path]['content'] = new TrustedHtml(preg_replace(
					'_(?<=["\'])././(.*?)\\1_',
					$this->mkurl('view'),
					static::$pages[$path]['content']
				));
		}
		return static::$pages[$path];
	}

	public function getTitle($params) {
		if($page = $this->fetchPage())
			return $page['title'];
	}

	public function view($params) {
		$content = new Content();
		if($page = $this->fetchPage()) {
			$canEdit = ACL::check('edit', $this, false, $params);
			$content->putVariables($page);
			if ($canEdit)
				$content->putVariable('editHref', $this->mkurl('edit'));
			return new BlockOutput($this, $content);
		} else if (ACL::check('edit', $this, false, $params))
			$this->redirect('edit');
		else
			throw new ItemNotFoundException($params['path'], 'page');
	}

	public function edit($params) {
		ACL::required('edit', $this, false, $params);
		$content = new Content();
		$input = new Content();
		$content->putNamespace('input', $input);
		if($page = $this->fetchPage($this->params['path'])) {
			$content->putVariables($page);
			$content->putVariables(array(
					'exists' => true,
					'path' => $this->params['path'],
				));
			$input->putVariables(array(
					'title' => new TextInput('title', $page['title']),
					'content' => new LongTextInput('content', $page['rawContent']),
				));
			if ($this->params['path'])
				$input->putVariable('delete', new ButtonInput('delete', 'Delete'));
		} else {
			$content->putVariables(array(
					'exists' => false,
					'path' => $this->params['path'],
				));
			$input->putVariables(array(
					'title' => new TextInput('title', $this->params['path']?end(explode('/', $this->params['path'])):'Homepage'),
					'content' => new LongTextInput('content', ''),
				));
		}
		$content->putVariables(array(
				'fileManager' => array('href' => $this->mkurl('files')),
				'imageManager' => array('href' => $this->mkurl('images')),
			));
		return new PostOutput(new BlockOutput($this, $content), $this->mkurl('doEdit'));
	}

	/**
	 *
	 * @param Content $content
	 */
	public function doEdit($params, $content) {
		ACL::required('edit', $this, false, $params);
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
		ACL::required('editFile', $this, false, $params);
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
		$page = $this->fetchPage($this->params['path']);
		$content = new Content();
		$input = new Content();
		$content->putNamespace('input', $input);

		$this->db->execute('SELECT `filename`, `file` FROM `files` WHERE `path` = $path', array(
				'path' => ltrim($params['path'], '/'),
			));
		$fileEntries = array();
		while($entry = $this->db->fetchrow())
			$fileEntries[$entry['filename']] = $entry['file'];
		$files = File::getLoadedInstances($this, $fileEntries);
		foreach($files as $file) {
			$content->addLoop('file', array_merge($file, array(
					'href' => $this->mkurl('file', array('filename'=>$file->getFilename())),
				)));

			$content->addLoop('image', array_merge($file, array(
					'href' => $this->mkurl('file', array('filename'=>$file->getFilename())),
					'thumb' => array(
							'href' => $this->mkurl('file', array('filename'=>$file->getFilename()), NULL, array(
									'thumb' => 'thumb' // TODO: type of thumbnail
								)),
						),
				)));
		}
		if (ACL::check('editFile', $this, false, $params))
			$content->putVariables(array('upload' => true, 'remove' => true));
		$input->putVariables(array(
				'file' => new FileInput('file'),
			));
		return new PostOutput(new BlockOutput($this, $content), $this->mkurl('editFiles'), true);
	}

	public function images($params, $void) {
		return $this->files($params, $void, true);
	}

	public function file($params) {
		ACL::required('view', $this, true, $params);
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
