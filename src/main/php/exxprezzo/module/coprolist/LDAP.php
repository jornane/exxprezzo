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
		
		$i=0;
		foreach($searchsorted as $searchentry){
			if ($searchentry){
				$name = $searchentry->getValue('name');
				$photo = $searchentry->getValue('sAMAccountName').'.jpg';
				$commissions = $this->retrieveCommissions($searchentry->getValue('memberOf'));
				if (!empty($commissions))
					$copros[$i] = new Copro($name, $photo, $commissions);
				$i++;
			}
		}
		return $copros; 
	}
	
	//give this a string array with DNs. It will select the
	//values that contain OU=Commissies, and return
	//an array of the Commission class
	public function retrieveCommissions($dnlist){
		$commissions = array();
		
		
		if (!is_array($dnlist)){
			$dnlist = array($dnlist);	
		}
		
		$j = 0;
		for($i=0;$i<count($dnlist);$i++){
			$dn = $dnlist[$i];
			if (strpos($dn,'OU=Commissies')){
				//filter out the CN from the DN
				$start = strpos($dn,'CN=')+2;
				$sub1 = substr($dn,$start);
				$end = strpos($sub1,',')-1;
				$name = substr($sub1,1,$end);
				$commissions[$j] = new Commission($name,$dn);
				$j++;
			}
		}
		
		if (!empty($commissions)){
			return $commissions;
		} else return false;
	}
	
	public function getCopro($dn){
		//echo('Searching for copro with DN '.$dn.'<br />');
		$search = $this->ldap->search($dn);
		$searchentry = $search->shiftEntry();
		$name = $searchentry->getValue('name');
		$photo = $searchentry->getValue('sAMAccountName').'.jpg';
		$commissions = $this->retrieveCommissions($searchentry->getValue('memberOf'));
		$copro = new Copro($name, $photo, $commissions);
		
		return $copro;
	}
	
	public function getTestCopro($dn){
		new Copro('Marissa Hoek','mhoek.jpg',array(new Commission('WWW-commissie',0),new Commission('Het allertofste bestuur ooit',0),new Commission('MeiscIAPC',0)));
	}
	
	public function getCoprosFromCommission($commission){
		$dn = $commission->getDN();
		//echo('Getting copros for '.$dn.'<br />');
		$search = $this->ldap->search($dn,null,array('attributes','member'));
		// Test for search errors:
		//if ($this->ldap->isError($search)) {
		//	die("could not connect to ldap: ".$search->getMessage());
		//}

		//Pop an entry from the searchlist and get the members from this entry
		$searchentry = $search->shiftEntry();
		$searchsorted = $searchentry->getValue('member');
		$len = count($searchsorted);
		$copros = array($len);
		for ($i = 0 ; $i<$len;$i++){
			//echo($searchsorted[$i]);
			$copros[$i] = $this->getCopro($searchsorted[$i]);
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
