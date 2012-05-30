<?php namespace exxprezzo\core\db;

abstract class SQL {

	protected $host = '';
	protected $port = '';
	protected $user = '';
	protected $pass = '';
	protected $dbname = '';
	protected $prefix = '';
	protected $flags = 0;
	protected $persist = false;
	protected $safechars = 'A-Za-z0-9 \\,\\.\\_\\-\\!\\#\\$\\%\\&\\\\\\(\\)\\=\\?\\+\\@\\/';
	
	protected $lastresult = NULL;
	protected $connectid = NULL;
	
	public function query($query, $values = array(), $debug = false) {
		return $this->fetchrows($this->execute($query, $values, $debug));
	}
	
	public static function createConnection($database, $namespace = NULL) {
		if (is_null($namespace)) $namespace = __NAMESPACE__;
		
		// Make an array with parameters
		$dbinfo = array_merge(array(
				'scheme' => '',
				'host' => '',
				'port' => '',
				'user' => '',
				'pass' => '',
				'path' => '',
				'query' => '',
				'fragment' => ''),
			parse_url($database));
		$classname = $namespace . '\\' . ucfirst(str_replace('sql', 'SQL', strtolower($dbinfo['scheme'])));
		return new $classname($dbinfo);
	}
	
	protected static function isUtf8($str) {
		$c=0; $b=0;
		$bits=0;
		$len=strlen($str);
		for($i=0; $i<$len; $i++){
			$c=ord($str[$i]);
			if($c > 128){
				if(($c >= 254)) return false;
				elseif($c >= 252) $bits=6;
				elseif($c >= 248) $bits=5;
				elseif($c >= 240) $bits=4;
				elseif($c >= 224) $bits=3;
				elseif($c >= 192) $bits=2;
				else return false;
				if(($i+$bits) > $len) return false;
				while($bits > 1){
					$i++;
					$b=ord($str[$i]);
					if($b < 128 || $b > 191) return false;
					$bits--;
				}
			}
		}
		return true;
	}
	
	/**
	 * Add prefixes to all given tables
	 *
	 * @param string $tables Comma seperated list of tables
	 * @return string Comma seperated list of tables
	 */
	protected function addPrefixes($tables, $noalias = false) {
		if(!trim($this->prefix)) return ' '.$tables.' '; // If no prefix, do nothing
		$tables = explode(',', $tables);
		foreach($tables as &$table) {
			$table = str_replace('`', '', trim($table)); // Make sure leading and trailing spaces, and backticks are removed
			$table = $this->prefix.$table . ((!$noalias and (strpos($table, ' ') === false)) ? ' `'.$table.'`' : NULL); // Add the prefix, and if no alias is set, set the original tablename as alias
		}
		return ' '.implode(',', $tables).' ';
	}
	
	/**
	 * Edit a query so it meets the requirements for this database layer
	 *
	 *
	 * @param string $query Query to edit
	 * @return string	The formatted query
	 * @todo Implement support for subqueries
	 */
	protected function fixquery($query, $values = array()) {
		$search = array(
				'/(?<=REPLACE|UPDATE|FROM|INTO|JOIN)[\\w]*(.*?)[\\w]*(?=\;|[A-Z]+ JOIN|ON|HAVING|WHERE|GROUP|LIMIT|ORDER|SET|VALUES|$)/s', // Will add a prefix before the tablename, and make an alias, if needed
				'/[\$|:]([\\w]+)(?=\\W|$)/', // Replace $... by the corresponding variabele
			);
		$this->lastValues = &$values;
		$query = preg_replace_callback($search[0], array($this, substr($query, 0, 6) == 'SELECT' ? '_addSelectPrefixes' : '_addNormalPrefixes'), $query);
		$query = preg_replace_callback($search[1], array($this, '_exportValue'), $query);
		/*
		  
		 $replace = array(
				substr($query, 0, 6) == 'SELECT'
				? '\' \'.${\'this\'}->addprefixes(\'$1\').\' \''
				: '\' \'.${\'this\'}->addprefixes(\'$1\', true).\' \'',
				'(isset($values[\'\\1\']) ? $this->exportvalue($values[\'\\1\']) : \'NULL\') . \' \\2\'',
		);
		return preg_replace($search, $replace, $query);
		*/
		return $query;
	}
	private function _addSelectPrefixes($matches) {
		return $this->addPrefixes($matches[1], false);
	}
	private function _addNormalPrefixes($matches) {
		return $this->addPrefixes($matches[1], true);
	}
	private function _exportValue($matches) {
		if (isset($this->lastValues[$matches[1]]))
			return $this->exportValue($this->lastValues[$matches[1]]);
		return $this->exportValue(NULL);
	}
	
	
	/**
	 * Export value in a safe form
	 *
	 * @param mixed $value The value to export
	 * @return string The value in a form that can be safely inserted in a query
	 */
	public function exportValue($value) {
		if ($value === true) return '1';
		if ($value === false) return '0';
		if (is_string($value)) return static::isUtf8($value)
		? '\''.mysql_real_escape_string($value, $this->connectid).'\''
		: '0x'.utf8_decode(bin2hex($value));
		// if (is_string($value)) return '\''.mysql_real_escape_string($value, $this->connectid).'\'';
		if (is_numeric($value)) return (string)$value;
		if (is_array($value)) {
			foreach($value as &$child) {
				$child = $this->exportValue($child);
			}
			return '('.implode(',', $value).')';
		}
		return 'NULL';
	}
	

}