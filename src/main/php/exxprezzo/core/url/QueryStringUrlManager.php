<?php namespace exxprezzo\core\url;

class QueryStringUrlManager extends AbstractUrlManager {
	
	public function __construct($server=NULL, $get=NULL, $post=NULL, $cookie=NULL, $env=NULL) {
		parent::__construct($server, $get, $post, $cookie, $env);
		// Split up the PHP QUERY_STRING into the further NEXXOS_URI and nexxos QUERY_STRING
		$tmp = explode('?', $server['QUERY_STRING'], 2);
		while (sizeof($tmp) < 2)
			$tmp[] = NULL;

		$server['NEXXOS_PATH']  = $tmp[0];
		$server['QUERY_STRING'] = $tmp[1];
	}
	
	public function mkrawurl($hostGroup, $path, $get=array(), $fullUrl=false, $noGetForce=true) {
		
	}
	
	/**
	 * Return the URL to a specific physical path, relative to the nexxos root.
	 * 
	 * @param string $path	Physical path, must not start with a slash
	 * 
	 * @return string	Link to physical file
	 */
	public function serverpath($path) {
		
	}
	
}
