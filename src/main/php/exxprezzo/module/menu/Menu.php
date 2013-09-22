<?php namespace exxprezzo\module\menu;

use \exxprezzo\core\db\SQL;

use \exxprezzo\core\Core;
use \exxprezzo\core\Content;

use \exxprezzo\core\output\BlockOutput;

use \exxprezzo\core\module\AbstractModule;

class Menu extends AbstractModule {

	/**	@var SQL */
	protected $db;
	/** @var string[] */
	protected $params;

	protected static $functions = array();

	public function init() {
		parent::init();
		$this->db = $this->getModuleParam('db');
		$this->params = $this->getPathParameters();
	}

	public function getTitle($params) {
		return $this->getName();
	}

	public function menu() {
		$content = new Content();
		$this->db->execute('SELECT `moduleInstance`, `function`, `vars`, `caption` FROM `menu`');
		while($entry = $this->db->fetchrow()) {
			if (is_null($entry['moduleInstance']))
				$entry['url'] = $entry['path'];
			else {
				$module = AbstractModule::getInstance($entry['moduleInstance']);
				$entry['url'] = $module->mkurl($entry['function'], unserialize($entry['vars']));
			}
			$content->addLoop('menuItem', $entry);
		}
		return new BlockOutput($this, $content);
	}

}
