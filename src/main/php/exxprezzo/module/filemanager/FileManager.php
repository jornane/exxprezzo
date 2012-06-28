<?php namespace exxprezzo\module\filemanager;

use \exxprezzo\core\Core;

use \exxprezzo\core\module\AbstractModule;

class FileManager extends AbstractModule {
	
	public function getTitle($params) {
		return self::getName();
	}
	
	public static $storedir = 'upload/';
	
	/**
	 * 
	 * @param string $fileField
	 * @param AbstractModule $module
	 * @param int $oldID
	 */
	public static function storeUploadFile($module, $fileField, $oldID=NULL, $filename=NULL) {
		assert('is_string($fileField)');
		assert('$module instanceof \\exxprezzo\\core\\module\\AbstractModule');
		assert('is_null($oldID) || is_numeric($oldID)');
		
		if (!isset($_FILES[$fileField])) {
			throw new UploadException('There is no uploaded file by the name of '.$fileField);
		}
		$source = $_FILES[$fileField]['tmp_name'];
		switch($_FILES[$fileField]['error']) {
			case UPLOAD_ERR_INI_SIZE:user_error('The uploaded file exceeds the upload_max_filesize');break;
			case UPLOAD_ERR_FORM_SIZE:user_error('The uploaded file exceeds the MAX_FILE_SIZE directive');break;
			case UPLOAD_ERR_PARTIAL:user_error('The uploaded file was only partially uploaded');break;
			case UPLOAD_ERR_NO_FILE:return new File($module, $oldID); // The user chose not to upload anything; there is nothing wrong
			case UPLOAD_ERR_NO_TMP_DIR:user_error('Missing a temporary folder');break;
			case UPLOAD_ERR_CANT_WRITE:user_error('Failed to write file to disk during upload');break;
			case UPLOAD_ERR_EXTENSION:user_error('File upload stopped by extension');break;
		}
		assert('$_FILES[$fileField]["error"] == UPLOAD_ERR_OK');
		assert('$_FILES[$fileField]["size"]');
		assert('is_uploaded_file($source)');
		if (is_null($filename)) $filename = $_FILES[$fileField]['name'];
		$mimetype = $_FILES[$fileField]['type'];
		return self::storeFile($module, $source, $oldID, $filename, true, $mimetype);
	}
	
	/**
	 * Store a file
	 * if an $oldid is provided, is an integer and does already exist, it is overwritten, otherwise a new ID is made
	 * When the returned integer does not match the given $oldid, a new file is created, and nothing is overwritten
	 *
	 * @param \exxprezzo\core\module\AbstractModule	$module	The module this file belongs to
	 * @param string $fileSource	Location of file
	 * @param integer $oldid	Unique ID of an already existing file which needs to be overwritten. Provide NULL if you want a new file
	 * @param string $filename	Override filename
	 * @param boolean $isuploaded	Whether the file to store is uploaded by the user. This causes some extra checks to be peformed,
	 * 	additionally, the file may be moved after this function is executed.
	 * @return integer	Unique ID of file
	 */
	public static function storeFile($module, $fileSource, $oldID=NULL, $filename=NULL, $isUploaded=true, $mimetype=NULL) {
		assert('$module instanceof \\exxprezzo\\core\\module\\AbstractModule');
		assert('is_string($fileSource)');
		assert('is_null($oldID) || is_numeric($oldID)');
		assert('is_null($filename) || is_string($filename)');
		assert('is_bool($isUploaded)');
		assert('is_null($mimetype) || is_string($mimetype)');
		assert('function_exists("finfo_open") && function_exists("finfo_file") && function_exists("finfo_close")');
		
		$db = Core::getDatabaseConnection();
		
		if (is_null($mimetype)) {
			$finfo = finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
			$mimetype = finfo_file($finfo, $fileSource);
			assert('is_string($mimetype)');
			assert('strpos($mimetype, "/")');
			finfo_close($finfo);
		}
		$now = (integer)time();
	
		/*
		 * When $oldid is an integer, we probably need to update a file.
		* $oldid contains the Unique ID of a file, if it's correct, the $destination variable is set,
		* thus making sure that the code for adding a new file is not executed (it requires that $destination is not set)
		* The $result variable is also set from executing a query, when it evaluates to true,
		* the uploaded file will replace the existing file at the end of this function
		*
		* When $oldid is an integer, but it's not valid (e.g. not known in the database), it's ignored, and a new file is added,
		* because $destination is never set (as it could not be retrieved from the database)
		*/
		if (!is_null($oldID)) {
			if (is_writable(self::$storedir.$oldID)) {
				$result = $db->update('file', array(
						'filename' => $filename,
						'mimetype' => $mimetype,
						'touched' => $now,
						'updated' => $now,
					), array(
						'moduleInstance' => $module->getInstanceId(),
						'id' => (integer)$oldID,
					));
				if ($result) $id = (integer)$oldID;
				Thumbnail::delete($id);
			} else {
				throw new FileException('Cannot write to '.self::$storedir.$oldID);
			}
		}
	
		/*
		 * This block of code adds a new file to the database, rather than just updating an existing file.
		 * It gets executed when $destination is not set, meaning that no existing file could be found to update,
		 * either because no existing file was specified, so it's the desired behaviour,
		 * or the file to be upgraded could not be found in the database, or was not writable
		 */
		if (!isset($id)) {
			$result = $db->insert('file', array(
					'moduleInstance' => $module->getInstanceId(),
					'filename' => $filename,
					'mimetype' => $mimetype,
					'touched' => $now,
					'created' => $now,
				));
			if ($result)
				$id = $db->lastid();
		}
	
		/*
		 * PHP documentation states that when the target already exists, it's overwritten
		 * In case of an update that is exactly what we want, and the if-condition states that the file is writable
		 * In case of a new file it won't happen anyway, because the while loop states that the file cannot exist
		 * Only do it when $result evaluates to true, which means the last SQL query completed (it was either an insert or update)
		 */
		if ($result && isset($id) && $isUploaded
				? move_uploaded_file($fileSource, self::$storedir.$id)
				: copy($fileSource, self::$storedir.$id)
		) {
			return new File($module, $id);
		}
		user_error('Unable to store file at server');
	}

}
