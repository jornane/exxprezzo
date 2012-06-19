<?php namespace exxprezzo\module\coprolist;

use \exxprezzo\core\db\SQL;
use \exxprezzo\core\Core;
use \exxprezzo\core\Content;
use \exxprezzo\core\output\BlockOutput;
use \exxprezzo\core\module\AbstractModule;

class CoproList extends AbstractModule {
	protected static $functions = array(
			'(?<path>.*)' => 'coprolist',
	);
	
/* 	public function playground($params) {
		$content = new Content();
		$content->putVariable('imgHref', 'https://a248.e.akamai.net/camo.github.com/a312a1f49d93c7ebf86172aa61ff0afc823c01a6/687474703a2f2f6769746875622e73332e616d617a6f6e6177732e636f6d2f626c6f672f7265642d706f6c6f2e6a7067');
		return new BlockOutput($this, $content);
	} */
	
	public function coprolist($params){
		$content = new Content();
		//TODO: get copro's from LDAP and create objects
		
		//let's first make some ourselves
		$copro1 = new Copro('Yørn de Jong','pietjepuk.jpg',array(new Commission('WWW-commissie'),new Commission('Het allertofste bestuur ooit')));
		$copro2 = new Copro('Zoë Meijer','pietjepuk.jpg',array(new Commission('MeisciAPC'),new Commission('Kandidaatbestuur')));
		
		//TODO: return this list of copro's to the template
		$content->addLoop('coproItem', $copro1);
		$content->addLoop('coproItem', $copro2);
		
		return new BlockOutput($this, $content);
	}
	
	public function getTitle(){
		return 'Onze lieve coöperanten <3';
	}
	
}
?>