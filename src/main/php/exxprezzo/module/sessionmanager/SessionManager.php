<?php namespace exxprezzo\module\sessionmanager;

use \exxprezzo\core\url\HostGroup;

use \exxprezzo\core\Core;

use \exxprezzo\core\module\AbstractModule;

class SessionManager extends AbstractModule {
	
	protected $sid;//session id
	public $cookie_name;
	
	protected $allowInsecurePost = true;
	
	public $db;
	
	public function getTitle($params) {
		return 'Login';
	}
	
	public function init() {
		//parent::init();
		$this->db = $this->getModuleParam();
		
		$this->cookie_name = 'ESESSIONID';
		
		$uri = Core::getUrlManager();
		
		if ($uri->isGet()) {
			/**
			 * When the SID is available both as cookie and get parameter,
			 * kill the get parameter and reload
			 *
			 * The get and cookie may not be the same, but cookie has preference over get,
			 * and the GET parameter is the one being eliminated.
			 */
			if($uri->isInCookie($this->cookie_name)
					&& $uri->isInGet($this->cookie_name)) {
				$get = $uri->getRawGet();
				unset($get[$this->cookie_name]);
				$uri->redirect($uri->getHostGroup(), $uri->getPath(), $get);
			}
			
			/**
			 * Get SID from cookie if available, otherwise use get. Otherwise SID is NULL.
			 * When get is used, add the sid to forcedvars to make sure it's passed on.
			 * If we're dealing with a POST request, only allow get to make sure we're not dealing with a XSS attack
			 */
			if($uri->isInCookie($this->cookie_name)) { // if our session is in a cookie
				$this->sid = $uri->getCookie($this->cookie_name);
				$uri->forcePostVariable($this->cookie_name, $this->sid);
			} elseif($uri->isInGet($this->cookie_name)) { // if our session is in a get parameter
				$this->sid = $uri->getGet($this->cookie_name);
				$uri->forceVariable($this->cookie_name, $this->sid);
			} else { // if we have no session at all
				$this->sid = NULL;
			}
		} elseif ($uri->isPost()) {
			$this->sid = $uri->getPost($this->cookie_name);
			if (is_null($this->sid)) {
				/**
				 * No session ID posted, we're going to check the referer.
				 * Not posting the session ID means either XSS attack or a module which doesn't use sessions.
				 * A refercheck must protect against XSS attacks now.
				 */
				$refererData = parse_url($uri->getReferer());
				if ((!$this->allowInsecurePost || $uri->getHostGroup() != HostGroup::getInstance($refererData['host'], true))
						&& !$uri->$uri->isInGet($this->cookie_name)
						&& !$uri->isInCookie($this->cookie_name))
					throw new SecurityException(
							'Bad request. Please navigate to the homepage and make your request from there.',
							'The hostname from the referer does not match the current hostname');
				
				if($uri->isInCookie($this->cookie_name)) { // if our session is in a cookie
					$this->sid = $uri->getCookie($this->cookie_name);
					$uri->forcePostVariable($this->cookie_name, $this->sid);
				} elseif($uri->isInGet($this->cookie_name)) { // if our session is in a get parameter
					$this->sid = $uri->getGet($this->cookie_name);
					$uri->forceVariable($this->cookie_name, $this->sid);
				} else { // if we have no session at all
					$this->sid = NULL;
				}
			}
		} else {
			user_error('Unknown request method: '.$uri->getRequestMethod());
		}
	}
	
	/**
	 * 
	 * @param \exxprezzo\core\module\AbstractModule $module
	 * @return \exxprezzo\module\session\Session
	 */
	public function getSession($module) {
		//	if (!mt_rand(0,100))
			$this->cleanup();
		return new Session($this, $module->getInstanceId(), $this->sid);
	}
	
	/**
	 * Remove old sessions from the database
	 */
	protected function cleanup() {
		$this->db->execute('DELETE FROM `var` WHERE `touched`+`lifetime` < $now', array(
			'now' => time(),
		));
	}
	
}