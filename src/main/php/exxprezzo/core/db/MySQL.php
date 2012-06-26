<?php namespace exxprezzo\core\db;
/**
 * MySQL database layer
 * 
 * Instead of calling the PHP database commands,
 * you use this class for accessing the database.
 * This will make nexxos usable on multiple database types.
 *
 * @author Yorn de Jong, Jouke Witteveen
 * @copyright Yorn de Jong, Jouke Witteveen 2006-2007
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */

/**
 * MySQL database layer
 */

class MySQL extends SQL {
	/**
	 * List of all tables available to this databaselayer.
	 * Use the listtables function to read it,
	 * reset it to NULL when changes have been made
	 */
	private $tables = NULL;
	
	public $connected = false;
	public $lastquery = '';
	public $querycount = 0;
	
	public $debug = true; // Temporary, because i'm still developing shit; should be false
	
	private $translation = array(
		'varchar' => 'string',
		'int' => 'integer',
		'tinyint' => 'integer',
		'bigint' => 'integer',
		'text' => 'text',
		'blob' => 'binary',
		'tinyblob' => 'binary',
		'longblob' => 'binary',
	);
	
	/**
	 * @todo Nice error message when connecting fails in developer mode
	 */
	public function __construct($dbinfo) { // Constructor
		parent::__construct($dbinfo);
		
		// Login to MySQL
		if ($this->port) {
			if (!is_numeric($this->port) || $this->port < 0 || $this->port > 65535)
				user_error('Port in invalid range');
			$this->host .= ':'.$this->port;
		}
		$this->connectid = $this->persist ? mysql_pconnect($this->host, $this->user, $this->pass, $this->flags) : mysql_connect($this->host, $this->user, $this->pass, false, $this->flags);
		if (!$this->connectid) {
			throw new DatabaseConnectionException('Can\'t connect to database server');
		}
		
		// Make database connection
		$this->connected = mysql_select_db($this->dbname, $this->connectid);
		if (!$this->connected) {
			throw new DatabaseConnectionException('Database '.$this->dbname.' doesn\'t exist');
		}
		mysql_set_charset('utf8', $this->connectid); 
	}
	
	public function escape_str($str) {
		return mysql_real_escape_string($str);
	}
	
	/**
	 * Run a query
	 *
	 * Usage	example:
	 * <samp>
	 * // SELECT * FROM `table` WHERE `value` = 'bar'
	 * $db->query('SELECT * FROM `table` WHERE `value` = $foo', array('foo' => 'bar'));
	 * </samp>
	 * 
	 * @return resource
	 * @param string $query	Query to execute
	 * @param array $values	Values to be added to the query by the function (avoids MySQL injection)
	 * @param boolean $debug	If set to true, you'll get verbose output when a query fails
	 */
	public function execute($query, $values = array(), $debug = false) {
		if (!$this->connected) return false;
		if ($this->debug) $debug = true;
		$this->connected = mysql_select_db($this->dbname, $this->connectid);
		$result = mysql_query($this->lastquery = $this->fixquery($query, $values), $this->connectid);
		$this->querycount++;
		if (!$result)
			throw new DatabaseQueryException($this);
		return $this->lastresult = $result;
	}
	/**
	 * Fetch one row returned by a query (equivalent to mysql_fetch_array)
	 *
	 * @return array
	 */
	public function fetchrow($result=NULL, $assoc=true, $num=false) {
		if (!$this->connected) return false;
		if (is_null($result)) $result = $this->lastresult;
		if (is_null($result)) return false;
		$method = $assoc ? ($num ? MYSQL_BOTH : MYSQL_ASSOC) : ($num ? MYSQL_NUM : NULL);
		return mysql_fetch_array($result, $method);
	}
	/**
	 * Fetch all rows returned by a query
	 * @param resource $result	if not specified, last result set used
	 * @param string $idfieldname	Use this field as the key value
	 * @param boolean $assoc	Associative array
	 * @param boolean $num	Numeric array
	 *
	 * @return array
	 */
	public function fetchrows($result=NULL, $idfieldname=NULL, $assoc=true, $num=false) {
		if (!$this->connected) return false;
		$output = array();
		while ($tmp = $this->fetchrow($result, $assoc, $num)) {
			if (is_null($idfieldname)) {
				$output[] = $tmp;
			} else {
				$output[$tmp[$idfieldname]] = $tmp;
			}
		}
		return $output;
	}
	/**
	 * Tell how many results a query returned.
	 *
	 * Used like mysql_num_rows. If no parameters used, uses the last result set. If no last result set, returns false.
	 *
	 * @param resource $result if not specified, last result set used
	 * @return integer|false
	 */
	public function numrows($result=NULL) {
		if (!$this->connected) return false;
		if (is_null($result)) $result = $this->lastresult;
		if (is_null($result)) return false;
		return mysql_numrows($result);
	}
	/**
	 * Tell how many rows a query modified.
	 *
	 * Used like mysql_affected_rows.
	 *
	 * @return int
	 */
	public function affectedrows() {
		return mysql_affected_rows();
	}
	
	/**
	 * Give the last occurred error code
	 *
	 * @return integer
	 */
	public function getErrno($nronly = false) {
		return mysql_errno($this->connectid);

	}
	
	/**
	 * Give the last occurred error text
	 *
	 * @return string
	 */
	public function getError() {
		return mysql_error($this->connectid);

	}
	
	/**
	 * Return the key value created with the last INSERT query
	 */
	public function lastid() {
		if (!$this->connected) return false;
		//return mysql_insert_id($this->connectid);
		$this->connected = mysql_select_db($this->dbname, $this->connectid);
		$result = mysql_query('SELECT LAST_INSERT_ID()', $this->connectid);
		$row = mysql_fetch_array($result, MYSQL_NUM);
		return $row ? (integer)$row[0] : NULL;
	}
	
	/**
	 * Insert a row into a table
	 *
	 * @param string $table	The table to insert a row in
	 * @param array $values	The values to insert
	 */
	public function insert($table, $values) {
		if (!is_array($values) or empty($values)) return false;
		$query = 'INSERT INTO `'.mysql_real_escape_string($this->prefix.$table, $this->connectid).'` SET ';
		$queryok = false;
		foreach($values as $key => $value) {
			if (!is_string($key)) continue;
			$queryok = true;
			$query .= '`'.mysql_real_escape_string($key, $this->connectid).'`=';
			$query .= $this->exportvalue($value).',';
		}
		if (!$queryok) return false;
		$query = substr($query, 0, -1);
		$this->querycount++;
		$this->connected = mysql_select_db($this->dbname, $this->connectid);
		$this->lastresult = mysql_query($query, $this->connectid);
		$this->lastquery = $query;
		if (!$this->lastresult) throw new DatabaseQueryException($this);
		return $this->lastresult;
	}
	
	/**
	 * Replace a row in a table
	 *
	 * @param string $table	The table to replace a row in
	 * @param array $values	The new values of the row
	 */
	public function replace($table, $values) {
		if (!is_array($values) or empty($values)) return false;
		$query = 'REPLACE `'.mysql_real_escape_string($this->prefix.$table, $this->connectid).'` SET ';
		$queryok = false;
		foreach($values as $key => $value) {
			if (!is_string($key)) continue;
			$queryok = true;
			$query .= '`'.mysql_real_escape_string($key, $this->connectid).'`=';
			$query .= $this->exportvalue($value).',';
		}
		if (!$queryok) return false;
		$query = substr($query, 0, -1);
		$this->querycount++;
		$this->connected = mysql_select_db($this->dbname, $this->connectid);
		$this->lastresult = mysql_query($query, $this->connectid);
		$this->lastquery = $query;
		if (!$this->lastresult) throw new DatabaseQueryException($this);
		return $this->lastresult;
	}
	
	/**
	 * Do a simple update query
	 *
	 * @param string $table	The table to update
	 * @param array $values	The values to update
	 * @param array $conditions	All conditions that must be met to commit update
	 */
	public function update($table, $values, $conditions) {
		if (!is_array($values) or empty($values) or !is_array($conditions) or empty($conditions)) return false;
		$query = 'UPDATE `'.mysql_real_escape_string($this->prefix.$table, $this->connectid).'` SET ';
		$queryok = false;
		foreach($values as $key => $value) {
			if (!is_string($key)) continue;
			$queryok = true;
			$query .= '`'.mysql_real_escape_string($key, $this->connectid).'`=';
			$query .= $this->exportvalue($value).',';
		}
		if (!$queryok) return false;
		$query = substr($query, 0, -1).' WHERE';
		
		$conditions = func_get_args();
		array_shift($conditions); // Remove the first parameter; $table
		array_shift($conditions); // Remove the second parameter; $values
		$oldlength = strlen($query);
		$query .= $this->buildconditions($conditions);
		if ($oldlength == strlen($query)) return false; // Query hasn't been changed so no conditions were added
		
		$this->querycount++;
		$this->connected = mysql_select_db($this->dbname, $this->connectid);
		$this->lastresult = mysql_query($query, $this->connectid);
		$this->lastquery = $query;
		if (!$this->lastresult) throw new DatabaseQueryException($this);
		return $this->lastresult;
	}
	
	/**
	 * Do a simple delete query
	 *
	 * @param string $table	The table to update
	 * @param array $condition	All conditions that must be met to commit delete.
	 * 	Conditions are in the form of an array($fieldname => $value),
	 * 	the array can contain one or more conditions, all of them must be matched to commence deletion.
	 * 	More than one $condition variable can be provided, one of them must be matched to commence deletion.
	 */
	public function delete($table, $condition) {
		if (!is_array($condition) or empty($condition)) return false;
		$query = 'DELETE FROM `'.mysql_real_escape_string($this->prefix.$table, $this->connectid).'` WHERE';
		
		$conditions = func_get_args();
		array_shift($conditions); // Remove the first parameter; $table
		$oldlength = strlen($query);
		$query .= $this->buildconditions($conditions);
		if ($oldlength == strlen($query)) return false; // Query hasn't been changed so no conditions were added
		
		$this->querycount++;
		$this->connected = mysql_select_db($this->dbname, $this->connectid);
		$this->lastresult = mysql_query($query, $this->connectid);
		$this->lastquery = $query;
		if (!$this->lastresult) throw new DatabaseQueryException($this);
		return $this->lastresult;
	}
	
	/**
	 * Generate a list of all available tables with the prefix removed
	 * 
	 * @return array	List of all available tables
	 */
	public function listtables() {
		if (!is_null($this->tables))
			return array_keys($this->tables);
		
		$this->tables = array();
		$this->connected = mysql_select_db($this->dbname, $this->connectid);
		$resource = mysql_query('SHOW TABLES', $this->connectid);
		$length = strlen($this->prefix);
		if (!$this->prefix) {
			while($table = mysql_fetch_array($resource, MYSQL_NUM)) {
				$this->tables[$table[0]] = NULL;
			}
		} else {
			while($table = mysql_fetch_array($resource, MYSQL_NUM))
				if (strpos($table[0], 0, $length) === $this->prefix)
					$this->tables[substr($table[0], $length)] = NULL;
		}
		return array_keys($this->tables);
	}
	
	/**
	 * Fetch all fields from one table
	 * The resulting array can be used to rebuild the table
	 * 
	 * @todo Document
	 * 
	 * @param string $table	Tablename
	 * @return array	Two dimensional array containing all fields
	 */
	public function fetchfields($table) {
		if (is_null($this->tables))
			$this->listtables();
		if (!is_null($this->tables[$table]))
			return $this->tables[$table];
		
		$result = array();
		$this->connected = mysql_select_db($this->dbname, $this->connectid);
		$resource = mysql_query('SHOW COLUMNS FROM `' . mysql_real_escape_string($this->prefix . $table) . '`', $this->connectid);
		while($field = mysql_fetch_assoc($resource)) {
			if (!preg_match('_^([a-z]+?)\(([0-9]+?)\) ([a-z]+?)$_', $field['Type'], $matches))
				if (!preg_match('_^([a-z]+?)\(([0-9]+?)\)$_', $field['Type'], $matches))
					if (!preg_match('_^([a-z]+)$_', $field['Type'], $matches))
						die('No match for "'.$field['Type'].'"');
			
			$fielddata = array($field['Field'], $this->translation[$matches[1]]);
			if (isset($matches[2]))
				$fielddata[] = $matches[2];
			if (isset($matches[3])) {
				$fielddata[] = $matches[3];
			} else {
				if ($fielddata[1] == 'integer')
					$fielddata[] = 'signed';
			}
			if ($field['Null'] == 'YES')
				$fielddata[] = 'null';
			if ($field['Key'] == 'PRI')
				$fielddata[] = 'primary';
			
			$result[] = $fielddata;
		}
		return $this->tables[$table] = $result;
	}
	
	/**
	 * Create WHERE conditions from array
	 *
	 * @todo document
	 * 
	 * @param array $conditions	Array of arrays; every array holds one or more conditions which must all be met.
	 * @return string	WHERE condition
	 */
	private function buildconditions($conditions) {
		$queryok = false;
		$query = ' (';
		foreach($conditions as $condition) {
			$hasmatches = false;
			if (is_array($condition)) foreach($condition as $key => $value) {
				if (!is_string($key)) continue;
				$queryok = true;
				$hasmatches = true;
				$query .= '`'.mysql_real_escape_string($key, $this->connectid).'`=';
				$query .= $this->exportvalue($value).' AND ';
			}
			$query .= ($hasmatches?'1':'0').') OR (';
		}
		if (!$queryok) return '';
		return $query . '0) ';
	}
}
?>
