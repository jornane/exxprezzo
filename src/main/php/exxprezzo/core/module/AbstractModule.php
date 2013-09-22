<?php namespace exxprezzo\core\module;

use \Exception;

use \exxprezzo\core\db\SQL;

use \exxprezzo\core\Core;
use \exxprezzo\core\Content;
use \exxprezzo\core\Runnable;

use \exxprezzo\core\url\AbstractUrlManager;
use \exxprezzo\core\url\HostGroup;

abstract class AbstractModule implements Runnable {

	/** @var string */
	private $mainFunctionPath;
	/** @var string */
	private $modulePath;

	/** @var boolean */
	protected $isMain;
	/** @var string */
	private $functionName;
	/** @var string[] */
	private $pathParameters;

	/** @var int */
	private $instanceId;
	/** @var HostGroup */
	private $hostGroup;
	/** @var mixed */
	private $moduleParam;

	/** @var AbstractModule[] */
	private static $instances = array();

	/**
	 * Get the module instance associated with this hostGroup and internalPath
	 * @param HostGroup $HostGroup	the hostgroup
	 * @param string $internalPath	the requested path, without the path to the Exxprezzo installation
	 * @return AbstractModule	The module instance
	 */
	public static function getInstanceFor($hostGroup, $internalPath) {
		assert('$hostGroup instanceof \exxprezzo\core\url\HostGroup');
		assert('is_string($internalPath)');

		$path = ltrim($internalPath, '/');
		$dbh = Core::getDatabaseConnection();
		$options = AbstractUrlManager::pathOptions($path);
		$dbh->execute('SELECT `moduleInstanceId`, `module`, `root`, `param` FROM `moduleInstance`
				WHERE `root` IN :options AND `hostGroup` = :hostGroup
				ORDER BY LENGTH(`root`) DESC
				LIMIT 1', array('options' => $options, 'hostGroup' => $hostGroup->getId()));
		if ($instanceEntry = $dbh->fetchRow()) {
			$mainFunctionPath = '/'.substr($path, strlen(ltrim($instanceEntry['root'].'/', '/')));
			if ($mainFunctionPath == '/' && substr($path, -1) != '/' && $path != '')
				Core::getUrlManager()->redirect($hostGroup, $internalPath.'/');
			$instance = self::_instantiate(
						$instanceEntry['module'],
						$instanceEntry['moduleInstanceId'],
						$hostGroup,
						$instanceEntry['root'],
						$mainFunctionPath,
						self::parseParam($instanceEntry['param'])
					);
			return self::$instances[(int)$instanceEntry['moduleInstanceId']] = $instance;
		}
		// Make me a 404
		user_error('Unable to find suitable module.');
	}

	/**
	 * Get the module instance by ID, given the mainFunctionPath provided
	 * @param int $moduleInstanceId	the ID of the module instance to return
	 * @param string $mainFunctionPath	Give the instance this path, indicating it's main function
	 * @return AbstractModule	The module instance
	 */
	public static function getInstance($moduleInstanceId = NULL, $mainFunctionPath = NULL) {
		assert('is_null($moduleInstanceId) || is_numeric($moduleInstanceId)');
		assert('is_null($mainFunctionPath) || is_string($mainFunctionPath)');

		$dbh = Core::getDatabaseConnection();
		// TODO Find which instance should be returned, right now it's the first that pops up.
		if (is_null($moduleInstanceId)) {
			$dbh->execute('SELECT `moduleInstanceId`, `module`, `root`, `hostGroup`, `param` FROM `moduleInstance`
					WHERE `module` = :module
					ORDER BY LENGTH(`root`) DESC
					LIMIT 1', array(
							'module' => substr(get_called_class(), strrpos(get_called_class(), '\\')+1),
						)
				);
		} else {
			if(array_key_exists($moduleInstanceId, self::$instances))
				return self::$instances[$moduleInstanceId];
			$dbh->execute('SELECT `moduleInstanceId`, `module`, `root`, `hostGroup`, `param` FROM `moduleInstance`
					WHERE `moduleInstanceId` = :moduleInstanceId
					ORDER BY LENGTH(`root`) DESC
					LIMIT 1', array(
							'moduleInstanceId' => (int)$moduleInstanceId
						)
				);
		}
		if ($instanceEntry = $dbh->fetchrow()) {
			$moduleInstanceId = $instanceEntry['moduleInstanceId'];
			self::$instances[$moduleInstanceId] =
				self::_instantiate(
						$instanceEntry['module'],
						$moduleInstanceId,
						new HostGroup($instanceEntry['hostGroup']),
						$instanceEntry['root'],
						$mainFunctionPath,
						self::parseParam($instanceEntry['param'])
					);
			return self::$instances[$moduleInstanceId];
		}
		if (is_null($moduleInstanceId))
			user_error('No instance was found for module '.substr(get_called_class(), strrpos(get_called_class(), '\\')+1));
		else
			user_error('There is no module with instance '.$moduleInstanceId);
	}

	/**
	 *
	 * @param string $class
	 * @return SingletonModule
	 */
	public static function getSingleton($class) {
		assert('is_string($class)');
		assert('is_subclass_of($class, \'\\\\exxprezzo\\\\core\\\\module\\\\SingletonModule\'');
		return $instance;
	}

	/**
	 *
	 * @param string $module
	 * @param int $instanceId
	 * @param HostGroup $hostGroup
	 * @param string $modulePath
	 * @param string $mainFunctionPath
	 * @param mixed $param
	 */
	private static function _instantiate($module, $instanceId, $hostGroup, $modulePath, $mainFunctionPath, $param) {
		$moduleFQN = '\\exxprezzo\\module\\'.strtolower($module).'\\'.$module;
		/** @var \exxprezzo\core\module\AbstractModule */
		$result = new $moduleFQN;
		$result->instanceId = $instanceId;
		$result->hostGroup = $hostGroup;
		$result->modulePath = $modulePath;
		$result->mainFunctionPath = $mainFunctionPath;
		$result->moduleParam = $param;
		$result->init();
		return $result;
	}

	/**
	 *
	 * @param mixed $moduleParameter
	 * @throws \Exception
	 */
	public static function parseParam($moduleParameter) {
		try {
			return unserialize($moduleParameter);
		} catch (Exception $e) {
			if (!parse_url($moduleParameter))
				throw $e;
			return SQL::createConnection($moduleParameter);
		}
	}

	/**
	 * @return string
	 */
	public abstract function getTitle($params);

	/**
	 * Will set if the module is main
	 * If true and no function has been determined earlier,
	 * an attempt to add a slash to the internal_path is done.
	 * If this does not correct the problem, an error is given
	 * @param boolean $isMain
	 */
	public function setMain($isMain) {
		$this->isMain = $isMain;
		if ($this->isMain() && !$this->getFunctionName()) {
			if (substr($this->mainFunctionPath, -1) != '/') {
				foreach(static::$functions as $regex => $function) {
					if (preg_match('/^'.str_replace('/', '\\/', $regex).'$/', $this->mainFunctionPath.'/'))
						Core::getUrlManager()->redirect(
								Core::getUrlManager()->getHostGroup(),
								Core::getUrlManager()->getInternalPath().'/'
						);
				}
			}
			user_error('No function matches the function path "'.$this->mainFunctionPath.'" for module '.$this->getName());
		}
	}
	/**
	 * @return boolean
	 */
	public function isMain() {
		return $this->isMain;
	}

	/**
	 * Retrieves a path that will result in a call to the given function
	 * with the given arguments. This function uses a static field named $paths
	 * that subclasses need to fill to provide the paths needed for the function.
	 * The path is an ordinary URL except where the subclass wants an argument
	 * to appear. This can be achieved by placing a place holder {$name} which
	 * will then be replaced by $args['name'] when this function is called. This
	 * function returns the first shortest path created from $paths that first
	 * exactly fits args i.e. for each key in args a place holder appears in the
	 * path and the resulting length is minimal as compared to the possible values
	 * remaining in $paths.
	 * @param string $function the name of the function
	 * @param array $args the arguments to use when constructing the path
	 * @return string A path that maps to a call to $function with $args
	 * as its arguments
	 */
	public static function mkFunctionPath($function, $args) {
		assert('is_string($function);');
		assert('is_array($args);');

		if(!isset(static::$paths))
			user_error('This module has no usable paths');
		if(!isset(static::$paths[$function]))
			user_error('This function has no paths in this module');
		$paths = static::$paths[$function];
		$result = NULL;
		foreach($paths as $candidate) {
			$vars = static::extractVars($candidate);
			$candidateOK = true;
			foreach($vars as $var) {
				if (!isset($args[$var])) {
					$candidateOK = false;
					break;
				}
			}
			if($candidateOK) {
				$path = '/'.ltrim(static::buildFunctionPath($candidate, $args), '/');
				if(is_null($result) || strlen($path) < strlen($result) )
					$result = $path;
			}
		}
		if(is_null($result))
			user_error(
					'No suitable path found for function ' . $function
					. "\nArguments: " . var_export($args, true)
					. "\nCandidates considered: " . var_export($paths, true)
				);
		return $result;
	}

	/**
	 * Builds a function path from the given path and
	 * arguments. Each occurence of {$key} of args in path will
	 * be replaced by the value of $key in $args
	 * @param string $path the path to build the function path on
	 * @param array $args arguments to use when building the path
	 * @return $path with all occurences of named parameters
	 * replaced by values from $args
	 */
	private static function buildFunctionPath($path, $args) {
		// Build the needle and replace
		$needle = array();
		$replace = array();
		foreach($args as $key => $arg) {
			$needle[] = '{$' . $key . '}';
			$replace[] = $arg;
		}
		return str_replace($needle, $replace, $path);
	}

	/**
	 * Extracts the variables from a given path
	 *
	 * The path is a path string in which variable
	 * segments are given as {$name}. This will
	 * return a set of all names encountered in
	 * the path
	 * @param string $path The path to extract
	 * the variables from
	 * @return array A set of the variable names contained
	 * in path
	 */
	private static function extractVars($path) {
		$regex = '/({\\$(?<name>.*?)})/';
		$mathes = array();
		preg_match_all($regex,$path,$mathes);
		$names = $mathes['name'];
		$result = array();
		foreach($names as $name){
			$result[$name] = NULL;
		}
		return array_keys($result);
	}

	/**
	 *
	 * @param string[] $params
	 */
	protected function setPathParameters($params){
		$this->pathParameters = $params;
	}

	/**
	 * @return string[]
	 */
	public function getParameters(){
		return $this->pathParameters;
	}

	/**
	 * Get the module path (the part of the internal path that points to this module)
	 * @return string
	 */
	public function getModulePath() {
		return is_null($this->modulePath) ? NULL : $this->modulePath;
	}
	/**
	 * Get the host group for this module
	 */
	public final function getHostGroup() {
		return $this->hostGroup;
	}

	/**
	 *
	 * @param string $name
	 */
	protected function setFunctionName($name){
		if (method_exists($this, $name))
			$this->functionName = $name;
		else
			user_error('Module '.$this->getName().' does not contain a function '.$name);
	}

	/**
	 * @return string
	 */
	public function getFunctionName() {
		return $this->functionName;
	}

	/**
	 * Initializes the module. This is the method that is called
	 * shortly after the module is constructed and it has been
	 * fully supplied with the information about its instance.
	 * Prior to this call the values of the variables related
	 * to this instance are not yet valid so if a module needs
	 * to do initialization based on that information it should
	 * do so here. The standard implementation finds a function
	 * for the internal path through the regular expression keys
	 * in static::$functions, parses arguments from it and then stores
	 * this infomation so run can call the function with the
	 * correct arguments. This also ensures getFunctionName()
	 * returns a sensible value. Any overriden version needs to
	 * at least maintain this behaviour (The function does not
	 * have to be based on static::$functions but getFunctionName()
	 * has to make sense).
	 */
	protected function init() {
		if (!isset(static::$functions))
			user_error('The default init implementation requires a $functions static property in the module class');
		$matches = NULL;
		foreach(static::$functions as $regex => $function) {
			$rawMatches = array();
			if (preg_match('/^'.str_replace('/', '\\/', $regex).'$/', $this->mainFunctionPath, $rawMatches)) {
				$matches = array();
				foreach($rawMatches as $name => $value)
					if (!is_integer($name))
						$matches[$name] = rawurldecode($value);
				$this->setPathParameters($matches);
				$this->setFunctionName($function);
				return;
			}
		}
	}

	/**
	 * Runs this module. This is done by calling the getFunctionName()
	 * method on this object. This name must be set to a valid value
	 * in init() (@see #init())
	 */
	public final function run() {
		$name = $this->getFunctionName();
		if (is_null($name))
			user_error('No function set');
		if (!method_exists($this, $name))
			user_error('Invalid function '.$name.' for module '.$this->getName());
		$content = new Content();
		$content->putVariables(Core::getUrlManager()->getRawPost());
		return $this->$name($this->getParameters(), $content);
	}

	/**
	 *
	 * @param string $function
	 * @param array $moduleParam
	 * @param boolean $fullUrl
	 * @param array $get
	 * @param boolean $noGetForce
	 */
	public final function mkurl($function, $moduleParam=NULL, $fullUrl=false, $get=array(), $noGetForce=false) {
		if (is_null($moduleParam))
			$moduleParam = $this->getParameters();
		else
			$moduleParam = array_merge($this->getParameters(), $moduleParam);
		if (is_null($this->getModulePath()))
			user_error('Module '.$this->__toString().' is not exposed and as such no url can be made pointing to it.');
		if (is_null($fullUrl))
			$fullUrl = false;

		assert('is_string($function);');
		assert('is_array($moduleParam);');
		assert('is_bool($fullUrl);');
		assert('is_array($get);');
		assert('is_bool($noGetForce);');

		$functionPath = static::mkFunctionPath($function, $moduleParam);
		assert('is_string($functionPath)');
		assert('strlen($functionPath) > 0');
		assert('$functionPath{0}=="/"');
		assert('strlen($functionPath) == 1 || $functionPath{1}!="/"');
		return Core::getUrlManager()->mkurl(
				$this->getHostGroup(),
				rtrim('/'.$this->getModulePath(), '/').$functionPath,
				$get,
				$fullUrl,
				$noGetForce
			);
	}

	/**
	 *
	 * @param string $function
	 * @param array $moduleParam
	 * @param array $get
	 * @param boolean $noGetForce
	 */
	public final function redirect($function, $moduleParam=NULL, $get=array(), $noGetForce=true) {
		header('Location: '.$this->mkurl($function, $moduleParam, true, $get, $noGetForce));
		exit;
	}

	/**
	 * Module path is the part of the Internal URL which does not indicate the module.
	 * @return string	The module path
	 */
	public function getMainFunctionPath() {
		return $this->mainFunctionPath;
	}

	/**
	 * The module parameter is a variable that is provided to this instance of the module.
	 * There are no rules for this variable,
	 * because it differs for every module.
	 * It is however likely that this parameter will be a database connection.
	 * @return mixed	The module parameter
	 */
	public function getModuleParam() {
		return $this->moduleParam;
	}

	/**
	 * @return int	The instance number of this module instance
	 */
	public final function getInstanceId() {
		return $this->instanceId;
	}

	/**
	 * @return string	The name of this module
	 */
	public static final function getName() {
		$class = get_called_class();
		return substr($class, strrpos($class, '\\')+1);
	}

	/**
	 * @return string	String which can be used to identify this instance
	 */
	public final function __toString() {
		return self::getName().':'.$this->getInstanceId();
	}
}
