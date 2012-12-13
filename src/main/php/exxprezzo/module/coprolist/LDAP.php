<?php
namespace exxprezzo\module\coprolist;
error_reporting(E_ERROR);
require_once 'Net/LDAP2.php';
use \Net_LDAP2;
use \PEAR;

class LDAP{
	public function __construct(){
		$config = array(
				'binddn' => 'vercie@iapc.utwente.nl',
				'bindpw' => 'wuppie',
				'basedn' => 'ou=IAPC',
				'host' => 'ldap.private',
				'options' => array('LDAP_OPT_REFERRALS' => 0)
		);
		$this->ldap = Net_LDAP2::connect($config);
		if ($this->ldap instanceof Net_LDAP2_Error) {
			die("could not connect to ldap: ".$this->ldap->getMessage());
		}
	}
	
	/*
	 * Get all members of this LDAP group
	 * @returns an array of Commission which is actually an LDAP Group in this case (don't ask)
	 */
	public function getAllFromGroup($ou){
		$search = $this->ldap->search($ou);
		
		// Test for search errors:
		if ($search instanceof Net_LDAP2_Error) {
			die("could not connect to ldap: ".$search->getMessage());
		}
		
		$len = $search->count();
		$searchsorted = $search->sorted();
		
		$commissions = array();
		for ($i = 1 ; $i<$len;$i++){
			$DN = $searchsorted[$i]->getValue('distinguishedName', 'single');
			$CN = $searchsorted[$i]->getValue('CN', 'single');
			//Skip Oud Bestuur because it's stupid
			if (strcmp($CN,'Oud Bestuur')){
				$commissions[$i-1] = new Commission($CN,$DN);
			}
		}
		
		return $commissions;
	}	

	public function getAllCopros(){
		//echo('getting all copro\'s');
		$search = $this->ldap->search('OU=Copros,OU=IAPC,DC=iapc,DC=utwente,DC=nl');
		$searchsorted = $search->sorted();
		$searchsorted[0] = false; //remove the root
		
		$copros = array();
		
		foreach($searchsorted as $searchentry){
			if ($searchentry){
				$copro = $this->retrieveCopro($searchentry);
				//put this in a temp value for empty()
				$cc = $copro->getCommissions();
				if (!empty($cc))
					$copros[] = $copro;
			}
		}
		return $copros; 
	}
	
	//returns a Copro-object from a searchentry
	//this includes the commissions, photo URL,
	//board and name.
	//returns null if no Copro is found with this name
	//should probably only be used if the search entry is
	//really a copro
	public function retrieveCopro($searchentry){
		$name = $searchentry->getValue('name');
		$photo = $searchentry->getValue('sAMAccountName').'.jpg';
		$commissions = array();
		$board = null;
		
		$dnlist = $searchentry->getValue('memberOf');
		
		if (!is_array($dnlist)){
			$dnlist = array($dnlist);	
		}
		
		for($i=0;$i<count($dnlist);$i++){
			$dn = $dnlist[$i];
			if (strpos($dn,'OU=Commissies')){
				//filter out the CN from the DN
				$cname = $this->getBetween($dn,'CN=',',');
				$commissions[] = new Commission($cname,$dn);
			}
			if (strpos($dn,'OU=Besturen')){
				//filter out the CN from the DN
				$board = $this->getBetween($dn,'CN=',',');
			}
		}
		
		$copro = new Copro($name,$photo,$commissions,$board);
		return $copro;
	}
	
	//returns the first string found between $startstring and $endstring
	//not safe right now, use if you know it will return something
	public function getBetween($mainstring,$startstring,$endstring){
		$start = strpos($mainstring,$startstring)+strlen($startstring)-1;
		$sub1 = substr($mainstring,$start);
		$end = strpos($sub1,$endstring)-1;
		$result = substr($sub1,1,$end);
		return $result;
	}
	
	public function getCopro($dn){
		//echo('Searching for copro with DN '.$dn.'<br />');
		$search = $this->ldap->search($dn);	
		//var_dump($dn);
		$searchentry = $search->shiftEntry();
		$copro = $this->retrieveCopro($searchentry);		
		return $copro;
	}
	
	public function getTestCopro($dn){
		new Copro('Marissa Hoek','mhoek.jpg',array(new Commission('WWW-commissie',0),new Commission('Het allertofste bestuur ooit',0),new Commission('MeiscIAPC',0)),null);
	}
	
	public function getCoprosFromCommission($commission){
		$dn = $commission->getDN();
		//echo('Getting copros for '.$dn.'<br />');
		$search = $this->ldap->search($dn,null,array('attributes','member'));
		//Test for search errors:
		if (!strcmp(get_class($search),'NET_LDAP2_Error')) {
			die("could not connect to ldap: ".$search->getMessage());
		}
		//Pop an entry from the searchlist and get the members from this entry
		$searchentry = $search->shiftEntry();
		
		$copros = array();
		//Als er een foutmelding is moet dit niet uitgevoerd worden
		if (strcmp(get_class($searchentry),'PEAR_Error')){
			$searchsorted = $searchentry->getValue('member');
			//var_dump($searchsorted);
			if (!is_array($searchsorted)){
				$copros[] = $this->getCopro($searchsorted);
			}
			else {
				foreach($searchsorted as $result){
					$copros[] = $this->getCopro($result);
				}
			}
		}
		return $copros;
	}
	
	public function getAllCommissionsAsText($commissions){
		$result = "";
		$len = count($commissions);
		
		// Say how many entries we have found:
		$result = $result . "<p>Found " . $len . " entries!</p>";
		// Loop over all commissions and output the result
		for ($i = 0 ; $i<$len;$i++){
			$c = $commissions[$i];
			$result = $result . (string)$c . '<br />';
		}	
		
		return $result;
	}
}

?>