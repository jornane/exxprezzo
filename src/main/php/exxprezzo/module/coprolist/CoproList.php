<?php namespace exxprezzo\module\coprolist;

use \exxprezzo\core\db\SQL;
use \exxprezzo\core\Core;
use \exxprezzo\core\Content;
use \exxprezzo\core\output\BlockOutput;
use \exxprezzo\core\module\AbstractModule;

class CoproList extends AbstractModule {
	//(?<path>.*)
	protected static $functions = array(
			'/' => 'coprolist',
			'/(?<commission>.+)' => 'commissie',
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
		$copro1 = new Copro('Yørn de Jong','pietjepuk.jpg',array(new Commission('WWW-commissie',0),new Commission('Het allertofste bestuur ooit',0)));
		$copro2 = new Copro('Zoë Meijer','pietjepuk.jpg',array(new Commission('MeisciAPC',0),new Commission('Kandidaatbestuur',0)));
		
		//TODO: return this list of copro's to the template
		//$content->addLoop('coproItem', $copro1);
		//$content->addLoop('coproItem', $copro2);
		
		$ldap = new LDAP();
		
		//$commissions = $ldap->getAllCommissions();
		//$asdf = $ldap->getAllCommissionsAsText($commissions);
		
		//$coprotest = $ldap->getCoprosFromCommission($commissions[1]);	//start with 1, 0 is the root
		$coprotest = $ldap->getAllCopros();
		
		foreach ($coprotest as $c){
			$content->addLoop('coproItem',$c);
		}

		
		//$content->putVariable('test', $asdf);
		return new BlockOutput($this, $content);
	}
	
	public function getTitle($params){
		return 'Onze lieve coöperanten <3';
	}
	
	public function commissie($input){
		$commissionName = $input['commission'];
		$content = new Content();
		$ldap = new LDAP();
		
		$commission = new Commission($commissionName, 'CN='.$commissionName.',OU=Commissies,OU=IAPC,DC=iapc,DC=utwente,DC=nl');
		$copros = $ldap->getCoprosFromCommission($commission);
		foreach ($copros as $c){
			$content->addLoop('coproItem',$c);
		}
		return new BlockOutput($this, $content);
	}
	
}
?>