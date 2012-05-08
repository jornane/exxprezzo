<?php
/**
 * Nexxos kickstarter.
 *
 * @author Yorn de Jong
 * @copyright Yorn de Jong 2006-2012
 * @package nexxos
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License version 3
 */
$errorPage = dirname(__FILE__).'/error.html'; // In case something very bad goes wrong, don't expose it. View this page instead.

// Include core
if (!(include 'exxprezzo/core/'.DIRECTORY_SEPARATOR.'Core.php') || !class_exists('exxprezzo\\core\\Core')) {
	$msg = 'Could not initialize core';
	if (!is_null($errorPage) && file_exists($errorPage) && is_readable($errorPage)) {
		die(str_ireplace(array(
			'{message}',
			'{title}',
			'{caller}',
			'{stacktrace}',
		), array(
			$msg,
			'Initialization Error',
			'kickstarter',
			"\n".'at '.__FILE__.'('.__LINE__.')',
		), file_get_contents($errorPage)));
		exit;
	}
	die($msg);
}

function __autoload($className) {
	exxprezzo\core\Core::loadClass($className);
}

exxprezzo\core\Core::run();
