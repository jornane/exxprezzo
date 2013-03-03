<?php namespace exxprezzo\module\acl;

use \IteratorAggregate;
use \Iterator;

use \exxprezzo\core\Core;

use \exxprezzo\core\exception\PermissionException;

use \exxprezzo\core\module\AbstractModule;

class ACL extends AbstractModule {

	private static $debug = false;

	// Permission Types definitions
	const AT_COMP = 'c'; // Compare
	const AT_VAR = 'v'; // Variable
	const AT_ACL = 'a'; // ACL

	// Permissions Checks definitions
	const AC_GT = '>';
	const AC_LE = '<=';
	const AC_LT = '<';
	const AC_GE = '>=';
	const AC_EQ = '=';
	const AC_NE = '!=';
	const AC_IN = 'IN';
	const AC_NI = 'NIN';
	const AC_FI = 'FIRST';
	const AC_NF = 'NFIRST';
	const AC_LA = 'LAST';
	const AC_NL = 'NLAST';
	const AC_SU = 'SUB';
	const AC_NS = 'NSUB';
	const AC_BE = 'BEGIN';
	const AC_NB = 'NBEGIN';
	const AC_EN = 'END';
	const AC_ND = 'NEND';
	const AC_HA = 'HAS';
	const AC_NH = 'HASN';

	public function getTitle($params) {
		return 'ACL';
	}

	/**
	 * Check wether a permission is granted
	 *
	 * @param string $name	Name of the ACL to check (prefix will be added)
	 * @param AbstractModule $module	The module requesting the ACL check
	 * @param array $extravars	Extra variabeles you might want to submit for the ACL checking process
	 * @return boolean Whether the permission is granted, true when is_null($name)
	 */
	static public function check($name, $module, $defaultAccess = false, $extravars = array()) {
		if(is_null($name))
			return false;
		return self::checkACL($name, $module, $defaultAccess, array(
				'var' => $extravars,
				'module' => $module
			));
	}
	/**
	 * Require that a permission is granted, or throw an exception
	 *
	 * @param string $name	Name of the ACL to check (prefix will be added)
	 * @param AbstractModule $module	The module requesting the ACL check
	 * @param array $extravars	Extra variabeles you might want to submit for the ACL checking process
	 * @return boolean Whether the permission is granted, true when is_null($name)
	 */
	static public function required($name, $module, $defaultAccess = false, $extravars = array()) {
		if (!static::check($name, $module, $defaultAccess, $extravars))
			throw new PermissionException($name);
	}

	/**
	 * Check an ACL
	 *
	 * @param string $name	Name of the ACL
	 * @param AbstractModule $module	The module requesting the ACL check
	 * @param array $extravars	Extra variabeles you might want to submit for the ACL checking process
	 * @param array $prevacls	ACLs already being checked while recursing
	 * @return boolean Whether the permission is granted
	 */
	static private function checkACL($name, $module, $defaultAccess, $extravars = array(), $prevacls = array()) {
		if (in_array($name, $prevacls)) return false; // If this ACL is called by itself (maybe by recusion)
		$sqlresult = Core::getDatabaseConnection()->execute('SELECT
			`a`.`checkall`, `r`.`id`, `r`.`comptype`,
			`r`.`type1`, `r`.`data1`,
			`r`.`type2`, `r`.`data2`
			FROM `acl_acl` `a`, `acl_rule` `r`
			WHERE `a`.`name` = $name
			AND `r`.`acl` = `a`.`id`
		', array('name' => $name));
		$result = 0;
		$checks = 0;
		if (self::$debug) echo '<fieldset style="background:white;color:black;text-align:left"><legend>'.htmlspecialchars($name).'</legend>';
		while ($row = Core::getDatabaseConnection()->fetchrow($sqlresult)) {
			for($i=1;$i<=2;$i++) {
				$var = 'var'.$i;
				$$var = NULL;
				$type = $row['type'.$i];
				$data = $row['data'.$i];

				if($type == self::AT_COMP) $$var = unserialize($data); // @TODO catch error
				if($type == self::AT_VAR) $$var = Core::resolve($extravars, $data, '.');
				if($type == self::AT_ACL)
					$$var = self::checkACL($data, $module, $defaultAccess, array_merge($extravars, array('parentAcl' => $name)), array_merge($prevacls, array($name)));
			}

			if (self::$debug) {
				if ($row['comptype'] == self::AC_GT) { echo '<i>'.var_export($var1,true).'</i> &gt; <i>'.var_export($var2,true)."</i><br><b style=\"color:\n".($var1 > $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_LT) { echo '<i>'.var_export($var1,true).'</i> &lt; <i>'.var_export($var2,true)."</i><br><b style=\"color:\n".($var1 < $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_GE) { echo '<i>'.var_export($var1,true).'</i> ≥ <i>'.var_export($var2,true)."</i><br><b style=\"color:\n".($var1 >= $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_LE) { echo '<i>'.var_export($var1,true).'</i> ≤ <i>'.var_export($var2,true)."</i><br><b style=\"color:\n".($var1 <= $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_EQ) { echo '<i>'.var_export($var1,true).'</i> = <i>'.var_export($var2,true)."</i><br><b style=\"color:\n".($var1 == $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_NE) { echo '<i>'.var_export($var1,true).'</i> ≠ <i>'.var_export($var2,true)."</i><br><b style=\"color:\n".($var1 != $var2?'green">granCore':'red">rejec')."ted</b><br>\n<br>\n"; }
			}

			switch($row['comptype']) {
				case self::AC_GT:if($var1 > $var2) $result++;break;
				case self::AC_LT:if($var1 < $var2) $result++;break;
				case self::AC_GE:if($var1 >= $var2) $result++;break;
				case self::AC_LE:if($var1 <= $var2) $result++;break;
				case self::AC_EQ:if($var1 == $var2) $result++;break;
				case self::AC_NE:if($var1 != $var2) $result++;break;
				case self::AC_IN:if(Core::checkContains($var1, $var2)) $result++;break;
				case self::AC_NI:if(Core::checkContains($var1, $var2)) $result++;break;
				case self::AC_FI:if(Core::checkFirst($var1, $var2)) $result++;break;
				case self::AC_NF:if(Core::checkFirst($var1, $var2)) $result++;break;
				case self::AC_LA:if(Core::checkLast($var1, $var2)) $result++;break;
				case self::AC_NL:if(Core::checkLast($var1, $var2)) $result++;break;
				case self::AC_SU:if(Core::checkSubset($var1, $var2)) $result++;break;
				case self::AC_NS:if(Core::checkSubset($var1, $var2)) $result++;break;
				case self::AC_BE:if(Core::checkBegin($var1, $var2)) $result++;break;
				case self::AC_NB:if(Core::checkBegin($var1, $var2)) $result++;break;
				case self::AC_EN:if(Core::checkEnd($var1, $var2)) $result++;break;
				case self::AC_ND:if(Core::checkEnd($var1, $var2)) $result++;break;
				case self::AC_HA:if(Core::checkHas($var1, $var2)) $result++;break;
				case self::AC_NH:if(Core::checkHas($var1, $var2)) $result++;break;
			}
			$checks++;
			if (self::$debug) {
				if ($row['checkall'] == '0' and $result > 0) echo '<b style="color:green">Granted</b></fieldset>';
				if ($row['checkall'] == '1' and $result != $checks) echo '<b style="color:red">Rejected</fieldset>';
			}

			if ($row['checkall'] == '0' and $result > 0) return true;
			if ($row['checkall'] == '1' and $result != $checks) return false;

			// If the parser out of this loop, that means the query gave results and they have all been processed.
			// So if all needed to be true, we passed that. If only one had to be true, they all failed
			// Understanding this, it's obvious that the result is equal to wether all rules should be true.
			$final = ($row['checkall'] == '1');
		}
		if (self::$debug and $checks == 0) die('ACL '.htmlspecialchars($name).' contains no rules, <b style="color:">'.($final?'green">gran':'red">rejec').'t</b>');
		if ($checks == 0) return $defaultAccess;
		if (self::$debug) echo '<b style="color:'.($final?'green">gran':'red">rejec').'ted</b></fieldset>';
		return $final;
	}

}