<?php namespace exxprezzo\module\acl;

use \IteratorAggregate;
use \Iterator;

use \exxprezzo\core\Core;

use \exxprezzo\core\exception\PermissionException;

use \exxprezzo\core\module\AbstractModule;
use \exxprezzo\core\module\SingletonModule;

class ACL extends SingletonModule {

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
	static private function checkACL($name, $module, $defaultAccess, $extravars = array(), $prevacls = array(), $debug = false) {
		$db = Core::getDatabaseConnection();
		if ($module instanceof AbstractModule)
			$module = $module->getInstanceId();
		assert('is_numeric($module) || is_null($module)');
		if (in_array($name, $prevacls)) return false; // If this ACL is called by itself (maybe by recusion)
		$sqlresult = $db->execute('SELECT
			`a`.`checkall`, `r`.`id`, `r`.`comptype`, `a`.`debug`,
			`r`.`type1`, `r`.`data1`,
			`r`.`type2`, `r`.`data2`
			FROM `acl_acl` `a`, `acl_rule` `r`
			WHERE `a`.`name` = $name
			AND `r`.`acl` = `a`.`id`
			AND (`a`.`moduleInstance` = $moduleInstanceId OR `a`.`moduleInstance` IS NULL)
			ORDER BY `a`.`moduleInstance` IS NULL
		', array(
			'name' => $name,
			'moduleInstanceId' => $module,
		));
		$result = 0;
		$checks = 0;
		if ($debug)
			echo '<fieldset style="background:white;color:black;text-align:left"><legend>'.htmlspecialchars($name).'</legend>';
		while ($row = $db->fetchrow($sqlresult)) {
			if ($row['debug'] && !$debug) {
				echo '<fieldset style="background:white;color:black;text-align:left"><legend>'.htmlspecialchars($name).'</legend>';
				$debug = true;
			}
			for($i=1;$i<=2;$i++) {
				$var = 'var'.$i;
				$$var = NULL;
				$type = $row['type'.$i];
				$data = $row['data'.$i];

				if($type == self::AT_COMP) $$var = unserialize($data); // @TODO catch error
				if($type == self::AT_VAR) $$var = Core::resolve($data, $extravars, '.');
				if($type == self::AT_ACL)
					$$var = self::checkACL(
						$data,
						$module,
						$defaultAccess,
						array_merge($extravars, array('parentAcl' => $name)),
						array_merge($prevacls, array($name)),
						$debug
					);
			}

			if ($debug) {
				if ($row['comptype'] == self::AC_GT) { echo '<i>'.Core::describe($var1).'</i> &gt; <i>'.Core::describe($var2)."</i><br><b style=\"color:\n".($var1 > $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_LT) { echo '<i>'.Core::describe($var1).'</i> &lt; <i>'.Core::describe($var2)."</i><br><b style=\"color:\n".($var1 < $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_GE) { echo '<i>'.Core::describe($var1).'</i> ≥ <i>'   .Core::describe($var2)."</i><br><b style=\"color:\n".($var1 >= $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_LE) { echo '<i>'.Core::describe($var1).'</i> ≤ <i>'   .Core::describe($var2)."</i><br><b style=\"color:\n".($var1 <= $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_EQ) { echo '<i>'.Core::describe($var1).'</i> = <i>'   .Core::describe($var2)."</i><br><b style=\"color:\n".($var1 == $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
				if ($row['comptype'] == self::AC_NE) { echo '<i>'.Core::describe($var1).'</i> ≠ <i>'   .Core::describe($var2)."</i><br><b style=\"color:\n".($var1 != $var2?'green">gran':'red">rejec')."ted</b><br>\n<br>\n"; }
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
			if ($debug) {
				if ($row['checkall'] == '0' and $result > 0) echo '<b style="color:green">Granted</b></fieldset>';
				if ($row['checkall'] == '1' and $result != $checks) echo '<b style="color:red">Rejected</b></fieldset>';
			}

			if ($row['checkall'] == '0' and $result > 0) return true;
			if ($row['checkall'] == '1' and $result != $checks) return false;

			// If the parser out of this loop, that means the query gave results and they have all been processed.
			// So if all needed to be true, we passed that. If only one had to be true, they all failed
			// Understanding this, it's obvious that the result is equal to wether all rules should be true.
			$final = ($row['checkall'] == '1');
		}
		if ($checks == 0 && !is_null($module)) {
			$aclRec = $db->query('SELECT id FROM acl_acl WHERE name = $name AND moduleInstance = $moduleInstance', array(
				'name' => $name,
				'moduleInstance' => $module
			));
			if ($aclRec) {
				//$id = $aclRec[0]['id'];
			} else {
				$db->insert('acl_acl', array(
					'name' => $name,
					'moduleInstance' => $module,
					'debug' => '0'
				));
				$id = $db->lastid();
			}
			/*
			if ($id)
				$db->insert('acl_rule', array(
					'acl' => $id,
					'type1' => 'c',
					'data1' => serialize((boolean) $defaultAccess),
					'type2' => 'c',
					'data2' => 'b:1;',
				));
			*/
		}
		if ($checks == 0) return $defaultAccess;
		if ($debug) echo '<b style="color:'.($final?'green">gran':'red">rejec').'ted</b></fieldset>';
		return $final;
	}

}
