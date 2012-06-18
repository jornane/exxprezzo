<?php

use \exxprezzo\core\db\SQL;
use \exxprezzo\core\Core;
use \exxprezzo\core\Content;
use \exxprezzo\core\output\BlockOutput;
use \exxprezzo\core\module\AbstractModule;

class Playground extends AbstractModule {
	
	public function playground($params) {
		$content = new Content();
		$content->putVariable('imgHref', $this->mkurl('edit'));
		return new BlockOutput($this, $content);
	}
	
}
?>