<?php namespace exxprezzo\module\filemanager;

use \exxprezzo\core\module\AbstractModule;

use \exxprezzo\core\Core;

use \DateTime;

class File {
	
	/** @var \exxprezzo\core\module\AbstractModule */
	protected $module;
	protected $fileId;
	protected $filedata;
	
	public function __construct($module, $fileId) {
		assert('$module instanceof \exxprezzo\core\module\AbstractModule');
		assert('is_numeric($fileId)');
		assert('(int)$fileId==$fileId');
		$this->module = $module;
		$this->fileId = (int)$fileId;
	}
	
	/**
	 * Touch the file; set "touched" field to now
	 */
	public function touch() {
		$now = time();
		Core::getDatabaseConnection()->query('UPDATE `file` SET `touched` = $touched WHERE `id` = $file AND `moduleInstance` = $instanceId', array(
				'touched' => $now,
				'file' => $this->fileId,
				'instanceId' => $this->module->getInstanceId(),
			));
		$this->filedata['touched'] = $now;
	}
	
	/**
	 * Update the file; set "updated" and "touched" field to now
	 */
	public function update() {
		$now = time();
		Core::getDatabaseConnection()->query('UPDATE `file` SET `touched` = $updated, `updated` = $updated WHERE `id` = $file AND `moduleInstance` = $instanceId', array(
				'updated' => $now,
				'file' => $this->fileId,
				'instanceId' => $this->module->getInstanceId(),
		));
		$this->filedata['touched'] = $now;
		$this->filedata['updated'] = $now;
	}
	
	/**
	 * Retrieve the file and return the contents
	 *
	 * @return string	Contents of file
	 */
	public function fetch() {
		$now = time();
		if (is_readable(FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId)) {
			Core::getDatabaseConnection()->query('UPDATE `file` SET `touched` = $touched, `downloads` = `downloads`+1 WHERE `id` = $file AND `moduleInstance` = $instanceId', array(
					'touched' => $now,
					'file' => $this->fileId,
					'instanceId' => $this->module->getInstanceId(),
			));
			$this->filedata['touched'] = $now;
			$this->filedata['downloads']++;
			return file_get_contents(FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId);
		}
		return false;
	}
	public function passthru($ignoreheaders=false, $cache=NULL) {
		$now = time();
		$db = Core::getDatabaseConnection();
		
		if (!$ignoreheaders && headers_sent())
			user_error('Headers already sent');
		
		$db->execute('SELECT `filename`, `mimetype`, `updated`, `created` FROM `file` WHERE `id` = $file AND `moduleInstance` = $instanceId LIMIT 1', array(
			'file' => (integer)$this->fileId,
			'instanceId' => $this->module->getInstanceId(),
		));
		if ($filedata = $db->fetchrow()) if (is_readable(FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId)) {
			if (!headers_sent()) { // If headers are already sent, header() will fail anyway
				if (is_null($cache)) $cache = 3600; // @TODO read from config
				header('Content-type: '.$filedata['mimetype']);
				// Text and application mimetypes can contain malicious code. They should always be sent as attachment
				$attachment = $filedata['mimetype'] != 'text/plain' && substr($filedata['mimetype'], 0, 5) == 'text/' || substr($filedata['mimetype'], 0, 12) == 'application/';
				header('Content-Disposition: '.($attachment?'attachment; ':'').'filename="'.addslashes($filedata['filename']).'"');
				header('Content-length: '.filesize(FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId));
				header('Last-Modified: '.date(DateTime::RFC1123, is_null($filedata['updated'])?$filedata['created']:$filedata['updated']));
				if ($cache) {
					header('Cache-Control: max-age='.(integer)$cache.' public');
					header('Expires: '.date(DateTime::RFC1123, $now+$cache));
				} else {
					header('Cache-Control: no-cache no-store must-revalidate');
					header('Expires: '.date(DateTime::RFC1123));
				}
			}
			
			readfile(FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId);
			$db->query('UPDATE `file` SET `touched` = $touched, `downloads` = `downloads`+1 WHERE `id` = $file AND `moduleInstance` = $instanceId', array(
					'touched' => $now,
					'file' => $this->fileId,
					'instanceId' => $this->module->getInstanceId(),
				));
			$this->filedata['touched'] = $now;
			$this->filedata['downloads']++;
			return true;
		} else
			throw new FileException('File not readable: '.FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId);
	}
		
	/**
	 * Fetch filedata
	 *
	 * @param integer[] $file	Unique ID of file as integer, or an array of Unique ID's
	 * @return array	Indexed array with all information about the file (filename, mimetype, filesize, path, touched, updated, created).
	 * 	The last 3 are unix timestamps.
	 * 	When the parameter is an array, it is an two dimensional array, first dimension is the Unique ID, second dimension the fieldname
	 * @todo filesize() has a maximum size it can handle
	 */
	static public function getLoadedInstances($module, $files) {
		assert('$module instanceof \exxprezzo\core\module\AbstractModule');
		$db = Core::getDatabaseConnection();
		
		assert('is_array($files)');
		
		$query = 'SELECT `id`, `moduleInstance`, `filename`, `mimetype`, `touched`, `updated`, `created`, `downloads` FROM `file` WHERE `moduleInstance` = $instanceId AND (0';
		foreach($files as $curfile) {
			assert('is_numeric($curfile)');
			assert('(int)$curfile==$curfile');
			$query .= ' OR `id` = '.(integer)$curfile;
		}
		$query .= ')';
		$db->execute($query, array(
				'instanceId' => $module->getInstanceId(),
			));
	
		$result = array();
		while ($filedata = $db->fetchrow()) {
			$filedata['path'] = FileManager::$storedir.AbstractModule::getInstance($filedata['moduleInstance'])->getName().DIRECTORY_SEPARATOR.$filedata['moduleInstance'].DIRECTORY_SEPARATOR.$filedata['id'];
			//$filedata['path'] = FileManager::$storedir.$filedata['id'];
			if (is_readable($filedata['path'])) {
				$filedata['filesize'] = filesize($filedata['path']);
				$id = $filedata['id'];
				unset($filedata['id']);
				$result[$id] = new File($module, $id);
				$result[$id]->filedata = $filedata;
			}
		}
		return $result;
	}
	public function fetchdata() {
		if (!is_null($this->filedata))
			return $this->filedata;
		$db = Core::getDatabaseConnection();
		
		$query = 'SELECT `id`, `filename`, `mimetype`, `touched`, `updated`, `created`, `downloads` FROM `file` WHERE '
			. '`id` = '.$this->fileId.' AND `moduleInstance` = $instanceId LIMIT 1';
		$db->execute($query, array(
				'instanceId' => $this->module->getInstanceId(),
			));

		$filename = FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId;
		if ($filedata = $db->fetchrow()) if(is_readable($filename)) {
			$filedata['path'] = $filename;
			$filedata['filesize'] = filesize($filename);
			return $this->filedata = $filedata;
		}
		user_error('File not found: '.$filename);
	}
	
	public function getFileId() {
		return $this->fileId;
	}
	
	public function getFilename() {
		if (is_null($this->filedata))
			$this->fetchdata();
		return $this->filedata['filename'];
	}
	
	public function getMimetype() {
		if (is_null($this->filedata))
			$this->fetchdata();
		return $this->filedata['mimetype'];
	}
	
	public function getTouched() {
		if (is_null($this->filedata))
			$this->fetchdata();
		return $this->filedata['touched'];
	}
	
	public function getUpdated() {
		if (is_null($this->filedata))
			$this->fetchdata();
		return $this->filedata['updated'];
	}
	
	public function getCreated() {
		if (is_null($this->filedata))
			$this->fetchdata();
		return $this->filedata['created'];
	}
	
	public function getDownloads() {
		if (is_null($this->filedata))
			$this->fetchdata();
		return $this->filedata['downloads'];
	}
	
	public function getSize() {
		return filesize(FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId);
	}
	
	/**
	 * Delete a file
	 *
	 * @return boolean	True if removal succeeded
	 */
	public function delete() {
		$db = Core::getDatabaseConnection();
		if (
				!file_exists(FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId)
				|| unlink(FileManager::$storedir.$this->module->getName().DIRECTORY_SEPARATOR.$this->module->getInstanceId().DIRECTORY_SEPARATOR.$this->fileId))
		{
			Core::$db->query('DELETE FROM `file` WHERE `id` = $file AND `moduleInstance` = $instanceId LIMIT 1', array(
					'file' => $this->fileId,
					'instanceId' => $this->module->getInstanceId(),
				));
			Core::$db->execute('SELECT `id` FROM `file` WHERE `id` = $file AND `moduleInstance` = $instanceId LIMIT 1', array(
					'file' => $this->fileId,
					'instanceId' => $this->module->getInstanceId(),
			));
			return !Core::$db->fetchrow();
		}
		return false;
	}
		
}