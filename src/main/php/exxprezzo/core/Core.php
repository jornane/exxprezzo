<?php namespace exxprezzo\core;

use \exxprezzo\core\output\Output;
use \exxprezzo\core\output\ExceptionOutput;

use \exxprezzo\core\url\AbstractUrlManager;

use \exxprezzo\core\module\AbstractModule;
use \exxprezzo\core\layout\Page;

use \exxprezzo\core\exception\ExxprezzoException;

use \exxprezzo\core\db\SQL;

use \ArrayAccess;
use \ErrorException;
use \Exception;
use \Iterator;
use \IteratorAggregate;

final class Core {

	/** @var (string)[] */
	private static $config = array();

	/** @var string */
	private static $basedir;

	/** @var SQL */
	private static $database;

	/** @var string */
	private static $errorPage;

	/** @var AbstractUrlManager */
	private static $urlManager;

	/** @var \exxprezzo\core\page\Page */
	private static $pageManager;

	/** @var \exxprezzo\core\module\AbstractModule */
	private static $mainModule;

	public static function run() {
		self::$errorPage = $GLOBALS['errorPage'];
		try {
			self::$basedir = getcwd();
			// Set error handler
			set_error_handler(array('\exxprezzo\core\Core', 'handleError'));
			// Set assertion handler
			assert_options(ASSERT_CALLBACK, array('\exxprezzo\core\Core', 'handleAssertion'));

			define('PHPVERSION', (real)preg_replace("_^([0-9]+)\.([0-9]+)(.*)_", "\\1.\\2", phpversion()));
			assert('PHPVERSION >= 5.4');

			// Read config file
			self::readConfigFile();

			// Read config from database (implicit connect to database)
			self::readConfigFromDB();

			assert_options(ASSERT_ACTIVE, (!isset($config['debug'])||$config['debug'])?1:0);

			date_default_timezone_set(self::$config['timeZone']);

			// Parse URL
			/** @var string */
			$urlManager = 'exxprezzo\\core\\url\\'.self::$config['urlManager'].'UrlManager';
			self::$urlManager = new $urlManager();

			// Instantiate main module
			self::$mainModule = AbstractModule::getInstanceFor(
					self::$urlManager->getHostGroup(),
					self::$urlManager->getPath()
				);
			self::$mainModule->setMain(true);
			self::$urlManager->registerMainModule();

			// Invoke main module
			if (self::$urlManager->isGet()) try {
				/** @var \exxprezzo\core\Output */
				$outputObject = self::$mainModule->run();
			} catch (Exception $e) {
				if ($e instanceof ExxprezzoException)
					$e->writeErrorHeader();
				else
					header('HTTP/1.0 500 Internal Server Error', true, 500);
				$outputObject = new ExceptionOutput(self::$mainModule, $e);
			} else {
				self::$mainModule->run();
				throw new ConstraintsException('No redirect after a non-GET request.');
			}

			if ($outputObject instanceof Output) {
				if (Page::supportsOutput($outputObject)) {
					// Prepare output
					self::$pageManager = new Page($outputObject);

					// Send headers
					self::$pageManager->run();
				} else $outputObject->run();
			}

			// Cleanup
			if (!headers_sent() && ob_get_length() == 0)
				trigger_error('No errors occurred, but no output was generated either.');
		} catch (Exception $e) {
			Core::handleException($e, false, true);
			exit;
		}
	}

	/**
	 * @return \exxprezzo\core\url\AbstractUrlManager
	 */
	public static function getUrlManager() {
		return self::$urlManager;
	}

	public static function getMainModule() {
		return self::$mainModule;
	}

	private static function readConfigFile() {
		$contents = file_get_contents('exxprezzo/Config.php');
		$pos = strpos($contents, '?>', 0);
		self::$config = json_decode(substr($contents, $pos+2), true);
	}

	private static function readConfigFromDB() {
			foreach(self::getDatabaseConnection()->query('SELECT `key`, `value` from `config`') as $entry) {
			if (isset(self::$config[$entry['key']]))
				trigger_error('Duplicate config key: '.$entry['key']);
			try {
				self::$config[$entry['key']] = unserialize($entry['value']);
			} catch (ErrorException $void) {
				self::$config[$entry['key']] = $entry['value'];
			}
		}
	}

	public static function loadClass($className) {
		if (class_exists($className) || interface_exists($className)) return; // Class already exists, our work here is done
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
	/**
	 * @return \exxprezzo\core\db\SQL
	 */
	public static function getDatabaseConnection() {
		if (is_null(self::$database))
			self::$database = SQL::createConnection(self::$config['db']);
		return self::$database;
	}

	public static function handleError($errno, $errstr, $errfile, $errline, $context) {
		// Don't handle errors from external libraries
		if (self::$basedir != substr($errfile, 0, strlen(self::$basedir)))
			return false;
		// Convert errors to ErrorException
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
	public static function handleAssertion($file, $line, $assertion) {
		self::handleException(new \Exception('The following assertion was not met: '.$assertion));
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
			for($i=strrpos($className, '\\')+1;$i<strlen($className)-9;$i++) {
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
			if (isset($t['function']) && in_array($t['function'], array(
					'handleError',
					'handleException',
					'__autoload',
					'trigger_error',
					'user_error',
					'loadClass',
				))) {
					$tracestart = $id;
				}
		}
		//if ($tracestart > 0)
			//$trace = '#0 '.substr($e->getFile(), strlen(self::$basedir)).'('.$e->getLine().")\n";
		foreach($e->getTrace() as $id => $t) {
			if ($id >= $tracestart && isset($t['file']))
				$trace .= '#'.($id-$tracestart).' '.substr($t['file'], strlen(self::$basedir)).'('.$t['line'].'): '
					.$t['function'].'('.implode(',',array_map('gettype',$t['args'])).")\n";
		}

		// Try to render an errorpage
		if (!$output && !is_null(self::$errorPage) && file_exists(self::$errorPage) && is_readable(self::$errorPage)) {
			$output = str_ireplace(array(
				'{message}',
				'{title}',
				'{stacktrace}',
			), array(
				nl2br(htmlspecialchars($e->getMessage()), true),
				nl2br(htmlspecialchars($title.' Error'), true),
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

	public static function describe($var) {
		if (is_null($var))
			return 'NULL';
		$result = getType($var).' ';
		if (is_object($var))
			$result .= ' ('.get_class($var).')';
		elseif (is_array($var))
			$result = var_export($var, true);
		else
			$result .= var_export($var, true);
		return $result;
	}

	/**
	 * Retrieve the value of the variable indicated in $path.
	 * This function works by unpacking variables.
	 * $path is a $delim separated string which gets exploded on $delim
	 * and an iteration is performed on all segments of the path.
	 * 
	 * In each iteration step, we take $var and extract the name of
	 * the part from $var and replace $var with the newly obtained value.
	 * "extract the name" means that we will look at $var as a key/value store,
	 * being an array, object, overloaded object, ArrayAccess etc.
	 *
	 * Basically $path = foo.bar could yield the same result as
	 * $var['foo']->bar or $var->foo->getBar() or $var->getFoo()->__get('bar')
	 */
	public static function resolve($path, $var, $delim='.', $methodArgs=array(), &$log='') {
		assert('is_string($path)');
		assert('is_array($var) || is_object($var)');
		assert('strlen($delim) == 1');
		assert('is_array($methodArgs)');
		if (substr($path, 0, 2) == 'd:') {
			$path = substr($path, 2);
			$debug = true;
		}
		$log .= 'Resolve '.$path.' from '.getType($var);
		if (is_array($var))
			$log .= ' ('.implode(', ', array_keys($var)).')';
		if ($path == '')
			return $var;
		$segments = explode($delim, $path);
		if ($path[0] == '\\') {
			$var = call_user_func(array($segments[0], 'get'.ucfirst($segments[1])));
			$log .= "<br>\n".$segments[0]."::".'get'.ucfirst($segments[1])."<br>\n".getType($var)."<br>\n";
			array_shift($segments);
			array_shift($segments);
		}
		$remaining = $segments;
		while(!is_null($var) && count($segments) > 0 && count($remaining) > 0) {
			$result = NULL;
			while(is_null($result) && count($segments) > 0) {
				$key = implode($delim, $segments);
				$last = array_pop($segments);
				$method = 'get'.ucfirst($key);
				$log .= "\n<br>'".$key;
				if (is_array($var) && isset($var[$key])) try {
					$log .= ": arrayvar";
					$result = $var[$key]; // Primitive array
				} catch (Exception $e) {}
				else if (is_object($var) && isset($var->$key)) try {
					$log .= ": objectvar";
					$result = $var->$key; // Direct object access
				} catch (Exception $e) {}
				else if (is_object($var) && $var instanceof \ArrayAccess && $var->offsetExists($key)) try {
					// Object implementing ArrayAccess
					$log .= ": ArrayAccess";
					$result = call_user_func_array(array($var, 'offsetGet'), array_merge(array($key), $methodArgs));
				} catch (Exception $e) {}
				else if (is_object($var) && method_exists($var, '__get')) try {
					// Overloaded object access
					$log .= ": overload";
					$result = call_user_func_array(array($var, '__get'), array_merge(array($key), $methodArgs));
				} catch (Exception $e) {}
				else if (!strpos($method, $delim) && is_object($var) && method_exists($var, $method)) try {
					$log .= ": getter";
					$result = call_user_func_array(array($var, $method), $methodArgs); // Object with getter
				} catch (Exception $e) {}
				$log .= "'";
			}
			$log .= "<br>\n".getType($result);
			if (is_object($result))
				$log .= ' ('.get_class($result).')';
			$log .= "<br>\n";
			for($i=0;$i<count($segments)+1;$i++)
				array_shift($remaining);
			$segments = $remaining;
			$var = $result;
		}
		if (isset($debug))
			echo $log."<br>\n";
		return $var;
	}

	public static function checkContains($needle, $haystack) {
		if (is_array($haystack) || $haystack instanceof ArrayAccess)
			return in_array($needle, $haystack);
		elseif (is_string($haystack))
			return strpos($haystack, $needle) !== false;
		elseif (is_object($haystack) && $haystack instanceof IteratorAggregate)
			return static::checkIn($needle, $haystack->getIterator());
		elseif (is_object($haystack) && $haystack instanceof Iterator)
			foreach($haystack as $specimen)
				if ($specimen == $needle)
					return true;
		return false;
	}
	public static function checkFirst($needle, $haystack) {
		if (is_array($haystack))
			return reset($haystack) == $needle;
		elseif (is_string($haystack))
			return strpos($haystack, $needle) === 0;
		elseif (is_object($haystack) && $haystack instanceof IteratorAggregate)
			return static::checkFirst($needle, $haystack->getIterator());
		elseif (is_object($haystack) && $haystack instanceof Iterator)
			foreach($haystack as $specimen)
				if ($specimen == $needle)
					return true;
				else
					return false;
		return false;
	}
	public static function checkLast($needle, $haystack) {
		if (is_array($haystack))
			return end($haystack) == $needle;
		elseif (is_string($haystack))
			return strpos($haystack, $needle) === strlen($haystack)-strlen($needle);
		elseif (is_object($haystack) && $haystack instanceof IteratorAggregate)
			return static::checkLast($needle, $haystack->getIterator());
		elseif (is_object($haystack) && $haystack instanceof Iterator) {
			foreach($haystack as $specimen)
				$last = $specimen;
			if ($specimen == $last)
	 			return true;
	 	}
		return false;
	}
	public static function checkSubset($candidate, $subject) {
		if ($candidate instanceof IteratorAggregate)
			return static::checkSubset($candidate->getIterator(), $subject);
		elseif ($subject instanceof IteratorAggregate)
			return static::checkSubset(iterator_to_array($candidate->getIterator()), $subject);
		elseif ($subject instanceof Iterator)
			return static::checkSubset(iterator_to_array($candidate), $subject);
		elseif (is_object($candidate))
			return static::checkSubset(get_object_vars($candidate), $subject);
		elseif (is_array($candidate) || $candidate instanceof Iterator) {
			if (is_array($subject))
				foreach($candidate as $key => $value)
					if (!isset($subject[$key]) || $subject[$key] != $value)
						return false;
			elseif (is_object($subject))
				foreach($candidate as $key => $value)
					if (!isset($subject[$key]) || $subject->$key != $value)
						return false;
			return true;
		}
		return false;
	}
	public static function checkBegin($needle, $haystack) {
		if (is_object($haystack) && $haystack instanceof IteratorAggregate)
			return static::checkHas($needle, $haystack->getIterator());
		elseif (is_array($haystack) || $haystack instanceof Iterator) {
			end($haystack);
			return key($haystack) == $needle;
		}
		return false;
	}
	public static function checkEnd($needle, $haystack) {
		if (is_object($haystack) && $haystack instanceof IteratorAggregate)
			return static::checkHas($needle, $haystack->getIterator());
		elseif (is_array($haystack) || $haystack instanceof Iterator) {
			reset($haystack);
			return key($haystack) == $needle;
		}
		return false;
	}
	public static function checkHas($needle, $haystack) {
		if (is_array($haystack) || $haystack instanceof ArrayAccess)
			return isset($haystack[$needle]);
		elseif (is_object($haystack) && $haystack instanceof IteratorAggregate)
			return static::checkHas($needle, $haystack->getIterator());
		elseif (is_object($haystack) && $haystack instanceof Iterator)
			foreach($haystack as $key => $value) {
				if ($key == $needle)
					return true;
			}
		return false;
	}

}
