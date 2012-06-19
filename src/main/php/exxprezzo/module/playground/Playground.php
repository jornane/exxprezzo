<?php namespace exxprezzo\module\playground;

use \exxprezzo\core\db\SQL;
use \exxprezzo\core\Core;
use \exxprezzo\core\Content;
use \exxprezzo\core\output\BlockOutput;
use \exxprezzo\core\module\AbstractModule;

class Playground extends AbstractModule {
	protected static $functions = array(
			'(?<path>.*)' => 'playground',
	);
	
	public function playground($params) {
		$content = new Content();
		$content->putVariable('imgHref', 'https://a248.e.akamai.net/camo.github.com/a312a1f49d93c7ebf86172aa61ff0afc823c01a6/687474703a2f2f6769746875622e73332e616d617a6f6e6177732e636f6d2f626c6f672f7265642d706f6c6f2e6a7067');
		return new BlockOutput($this, $content);
	}
	
	public function getTitle(){
		return 'Playground';
	}
	
}
?>