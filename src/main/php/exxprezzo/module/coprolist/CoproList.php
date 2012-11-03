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
	
	public function coprolist($params){
		//The OU is given as a module parameter.
		//Besturen points to the besturenpage, coprolist to the commissies.
		$ou = $this->getModuleParam();
		
		$content = new Content();
		$ldap = new LDAP();
		$copros = $ldap->getAllCopros();
		//$commissies = $ldap->getAllCommissions();
		$commissies = $ldap->getAllFromGroup($ou);
		
		foreach($commissies as $c){
			$content->addLoop('commissieItem',$c);
		}
		
		//set the Base URL for links
		$content->putVariable('baseURL',Core::getURLManager()->mkurl($this->getHostGroup(), '/'.$this->getModulePath().'/', array(), FALSE, FALSE));

		return new BlockOutput($this, $content);
	}
	
	public function getTitle($params){
		return 'Onze lieve coöperanten <3';
	}
	
	public function commissie($input){
		$commissionName = $input['commission'];
		$content = new Content();
		$ldap = new LDAP();
		$ou = $this->getModuleParam();
		
		$commission = new Commission($commissionName, 'CN='.$commissionName.','.$ou);
		$copros = $ldap->getCoprosFromCommission($commission);
		foreach ($copros as $c){
			$content->addLoop('coproItem',$c);
		}
		$content->putVariable('baseURL',Core::getURLManager()->mkurl($this->getHostGroup(), '/'.$this->getModulePath().'/', array(), FALSE, FALSE));
		return new BlockOutput($this, $content);
	}	
}
?>