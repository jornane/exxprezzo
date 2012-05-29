<?php namespace exxprezzo\module\cms;

use exxprezzo\core\Content;

use exxprezzo\core\output\ContentOutput;

use exxprezzo\core\module\AbstractModule;

class CMS extends AbstractModule {
	
	protected static $functions = array(
			'(.*)' => 'view',
		);
	
	public function view() {
		$content = new Content();
		$content->putVariable('FOO', 'BAR');
		return new ContentOutput($this, $content);
	}
	
}
