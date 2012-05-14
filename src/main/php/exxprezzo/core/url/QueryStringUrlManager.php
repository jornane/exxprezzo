<?php namespace exxprezzo\core\url;

class QueryStringUrlManager extends AbstractUrlManager {
	
	public function __construct($server=NULL, $get=NULL, $post=NULL, $cookie=NULL, $env=NULL) {
		parent::__construct($server, $get, $post, $cookie, $env);
		// Split up the PHP QUERY_STRING into the further NEXXOS_URI and nexxos QUERY_STRING
		$tmp = explode('?', $server['QUERY_STRING'], 2);
		while (sizeof($tmp) < 2)
			$tmp[] = NULL;

		$this->server['BASE_URL'] = strpos($this->server['REQUEST_URI'], '?') === false
				? $this->server['REQUEST_URI']
				: substr($this->server['REQUEST_URI'], 0, strpos($this->server['REQUEST_URI'], '?'))
			;
		$this->server['INTERNAL_PATH'] = $tmp[0];
		$this->server['QUERY_STRING'] = $tmp[1];
		$this->internalPath = $tmp[0];
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
