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
		if (!$tmp[0] || $tmp[0]{0} != '/') {
			header('Location: '.$this->server['BASE_URL'].'?/'.$tmp[0]);
			exit;
		}
		if (strpos($tmp[0], '//') !== false) {
			header('Location: '.$this->server['BASE_URL'].'?'.preg_replace('_//+_', '/', $tmp[0]));
			exit;
		}
		$this->server['INTERNAL_PATH'] = $tmp[0];
		$this->server['QUERY_STRING'] = $tmp[1];
		$this->internalPath = $tmp[0];
	}
	
	public function mkurl($hostGroup, $path, $get=array(), $fullUrl=false, $noGetForce=true) {
		$base = $fullUrl ? $this->server['BASE_URL'] : '';
		// TODO merge $get with forced vars
		if ($get)
			return $base.'?'.$path.'?'.http_build_query($get);
		else
			return $base.'?'.$path;
	}
	
	/**
	 * Return the URL to a filesystem resource, for example a template resource.
	 * 
	 * @param string $path	Path to the filesystem resource
	 * 
	 * @return string	Link to physical file
	 */
	public function serverpath($path) {
		// The implementation for the QueryStringUrlManager is trivial;
		// in the URL, the basis and the internal path are divided by a question mark (?)
		return $path;
	}
	
}
