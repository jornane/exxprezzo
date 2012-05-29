<?php namespace exxprezzo\module\cms;

use exxprezzo\core\output\PartialStringOutput;

use exxprezzo\core\module\AbstractModule;

class CMS extends AbstractModule {
	
	protected static $functions = array(
			'(.*)' => 'view',
		);
	
	public function view() {
		return new PartialStringOutput($this, 'text/html', '<p>foo</p>');
	}
	
}
