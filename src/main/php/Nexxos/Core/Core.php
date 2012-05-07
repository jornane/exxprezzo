<?php
namespace Nexxos\Core {
	class Core {
		private static $config = array();
		private static $basedir;
		private static $database;
		private static $errorPage;
		private static $urlManager;
		private static $mainModule;
		
		public static function run() {
			self::$errorPage = $GLOBALS['errorPage'];
			try {
				self::$basedir = getcwd();
				// Set error handler
				set_error_handler(array('Nexxos\Core\Core', 'handleError'));
				
				// Read config file
				self::readConfigFile();
				
				// Read config from database (implicit connect to database)
				self::readConfigFromDB();
				
				// Parse URL
				$urlManager = 'Nexxos\\Core\\URL\\'.self::$config['urlManager'].'UrlManager';
				self::$urlManager = new $urlManager();
				
				// Instantiate main module
				self::$mainModule = \Nexxos\Core\Module\AbstractModule::getInstanceFor(
						self::$urlManager->getHostGroup(),
						self::$urlManager->getPath()
					);
				self::$mainModule->setMain(true);
				self::$mainModule->setUrlManager(self::$urlManager);
				
				// Invoke main module
				try {
					$outputObject = self::$mainModule->run();
				} catch (Exception $e) {
					$outputObject = new ExceptionOutput($e);
				}
				
				// Prepare output
				
				
				// Send headers
				
				
				// Send content
				
				
				// Cleanup
				if (!headers_sent())
					trigger_error('No errors occurred, but no output was generated either.');
			} catch (\Exception $e) {
				Core::handleException($e, false, true);
			}
		}
		
		private static function readConfigFile() {
			$contents = file_get_contents('Nexxos/Config.php');
			$pos = strpos($contents, '?>', 0);
			self::$config = unserialize(substr($contents, $pos+2));
		}
		
		private static function readConfigFromDB() {
			foreach(self::getDatabaseConnection()->query('SELECT `key`, `value` from `config`') as $entry) {
				if (isset(self::$config[$entry['key']]))
					trigger_error('Duplicate config key: '.$entry['key']);
				try {
					self::$config[$entry['key']] = unserialize($entry['value']);
				} catch (\ErrorException $void) {
					self::$config[$entry['key']] = $entry['value'];
				}
			}
		}
		
		public static function loadClass($className) {
			if (class_exists($className) || interface_exists($className)) return; // Class already exists, our work here is done
			if (substr($className, -9) == 'Exception' && strpos($className, '_') === false)
				$path = self::$basedir.DIRECTORY_SEPARATOR.'Nexxos'.DIRECTORY_SEPARATOR.'Exception'.DIRECTORY_SEPARATOR.$className.'.php';
			else
				$path = self::$basedir.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
			if (file_exists($path))
				include $path;
			if (!class_exists($className) && !interface_exists($className)) { // Throw a nice exception instead of letting PHP generate an ugly uncatchable error message
				// Errors can't be thrown from loadClass, so just handle it this way
				Core::handleException(new \Exception('Class \''.$className.'\' not found'));
				// If I don't do this, an unrecoverable error is the result
				exit;
			}
		}
		
		// Is public, but is only intended for use in the core namespace.
		public static function getDatabaseConnection() {
			if (is_null(self::$database))
				self::$database = new \PDO(
						self::$config['dbDSN'],
						self::$config['dbUser'],
						self::$config['dbPass'],
						self::$config['dbOpt']
					);
			return self::$database;
		}
		
		public static function handleError($errno, $errstr, $errfile, $errline, $context) {
			// Don't handle errors from external libraries
			if (self::$basedir != substr($errfile, 0, strlen(self::$basedir)))
				return false;
			// Convert errors to ErrorException
			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		}
		
		/**
		 * Display an Exception as HTML
		 *
		 * @param Exception $e	The exception to handle
		 * @param boolean $return	If this is true, the function will return the HTML instead of echoing it.
		 *	This will also cause the function to use the Theme class to render the error message, if possible
		 * @param boolean $handleHttpCode	Return the HTTP error code to the client
		 */
		public static function handleException($e, $return=false, $handleHttpCode=false) {
			$output = NULL;
			
			// Get the title of the error
			if (get_class($e) == 'ErrorException') {
				$title = 'PHP'; // Otherwise ErrorException would display as Error Error, now it displays as PHP Error
			} else if (get_class($e) == 'Exception') {
				$title = 'General';
			} else {
				$title = '';
				$className = get_class($e);
				for($i=0;$i<strlen($className)-9;$i++) {
					if (strtoupper($className{$i}) == $className{$i})
						$title .= ' ';
					$title .= $className{$i};
				}
			}
			
			// Build a stacktrace
			$trace = "\n";
			$tracestart = 0;
			// Make sure the stacktrace only goes so far as where handle_error, oops or panic got called
			foreach($e->getTrace() as $id => $t) {
				if (isset($t['function']) && in_array($t['function'], array('handleError', 'handleException', '__autoload', 'trigger_error')))
					$tracestart = $id+1;
			}
			if ($tracestart == 0)
				$trace = '#0 '.substr($e->getFile(), strlen(self::$basedir)).'('.$e->getLine().")\n";
			foreach($e->getTrace() as $id => $t) {
				if ($id >= $tracestart && isset($t['file']))
					$trace .= '#'.($id-$tracestart+($tracestart==0?1:0)).' '.substr($t['file'], strlen(self::$basedir)).'('.$t['line'].'): '
						.$t['function'].'('.implode(',',array_map('gettype',$t['args'])).")\n";
			}
			
			// Try to render an errorpage
			if (!$output && !is_null(self::$errorPage) && file_exists(self::$errorPage) && is_readable(self::$errorPage)) {
				$output = str_ireplace(array(
					'{message}',
					'{title}',
					'{stacktrace}',
				), array(
					htmlspecialchars($e->getMessage()),
					htmlspecialchars($title.' Error'),
					htmlspecialchars($trace),
				), file_get_contents(self::$errorPage));
			}
			// Use the default errorpage if that failed
			if (!$output) {
				$output = '<h1>'.htmlspecialchars($title)." Error</h1>\n<p>".nl2br($e->getMessage()).'</p><pre>'.$trace.'</pre>';
			}
			
			if (isset($output)) {
				if ($return)
					return $output;
				else {
					header('Content-Length: '.strlen($output)); // Content-Length makes HTTP 1.1 persistent connections possible
					echo $output;
				}
			}
		}
	}
}