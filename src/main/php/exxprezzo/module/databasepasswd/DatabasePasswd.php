<?php namespace exxprezzo\module\databasepasswd;

use \exxprezzo\core\output\StringOutput;

use \exxprezzo\core\Template;

use \exxprezzo\core\Content;
use \exxprezzo\core\Core;

use \exxprezzo\core\db\SQL;

use \exxprezzo\core\output\PostOutput;
use \exxprezzo\core\output\BlockOutput;

use \exxprezzo\core\input\PasswordInput;
use \exxprezzo\core\input\TextInput;

use \exxprezzo\module\sessionmanager\SessionManager;

use \exxprezzo\module\passwd\Passwd;

use \DateTime;

class DatabasePasswd extends Passwd {
	
	protected static $functions = array(
			'/login.html' => 'login',
			'/register.html' => 'register',
			'/password.html' => 'password',
			'/users/(?<user>.*)/(?<page>[^/]*)' => 'viewUser',
			'/groups/(?<group>.*)/(?<page>[^/]*)' => 'viewUser',
	);
	protected static $paths = array(
			'login' => array('login.html'),
			'register' => array('register.html'),
			'viewUser' => array('users/{user}'),
		);
	
	/** @var DatabaseUser[] */
	protected $usersByName, $usersById;
	/** @var DatabaseGroup[] */
	protected $groupsByName, $groupsById;
	/** @var \exxprezzo\core\db\SQL */
	public $db;
	/** @var boolean */
	protected $usersFullyLoaded, $groupsFullyLoaded = false;
	
	public function init() {
		parent::init();
		$this->db = $this->getModuleParam();
	}
	
	public function getTitle($params) {
		return 'Login';
	}
	
	public function getUsers() {
		$users = $this->db->query('SELECT `id`, `username` FROM `user`;');
		foreach($users as $userData) {
			if (isset($this->usersById[$userData['id']]))
				$user = $this->usersById[$userData['id']];
			elseif (isset($this->usersByName[$userData['name']]))
				$user = $this->usersByName[$userData['name']];
			else
				$user = new DatabaseUser($userData['name'], $userData['id']);
			$this->usersById[$userData['id']] = $user;
			$this->usersByName[$userData['name']] = $user;
		}
		$this->usersFullyLoaded = true;
	}
	public function getGroups() {
		$groups = $this->db->query('SELECT `id`, `username` FROM `group`;');
		foreach($groups as $userData) {
			if (isset($this->usersById[$userData['id']]))
				$user = $this->usersById[$userData['id']];
			elseif (isset($this->usersByName[$userData['name']]))
			$user = $this->usersByName[$userData['name']];
			else
				$user = new DatabaseUser($userData['name'], $userData['id']);
			$this->usersById[$userData['id']] = $user;
			$this->usersByName[$userData['name']] = $user;
		}
		$this->usersFullyLoaded = true;
	}
	public function getUserById($id) {
		assert('is_numeric($id)');
		if (!isset($this->usersById[$id]))
			$this->usersById[$id] = new DatabaseUser($this, NULL, $id);
		return $this->usersById[$id];
	}
	public function getGroupById($id) {
		assert('is_numeric($id)');
		if (!isset($this->groupById[$id]))
			$this->groupById[$id] = new DatabaseUser($this, NULL, $id);
		return $this->groupById[$id];
	}
	/**
	 * (non-PHPdoc)
	 * @see exxprezzo\module\passwd.Passwd::getUserByName()
	 * @return DatabaseUser
	 */
	public function getUserByName($name) {
		assert('is_string($name)');
		if (!isset($this->usersByName[$name]))
			$this->usersByName[$name] = new DatabaseUser($this, $name, NULL);
		return $this->usersByName[$name];
	}
	public function getGroupByName($name) {
		assert('is_string($name)');
		if (!isset($this->groupByName[$name]))
			$this->groupByName[$name] = new DatabaseUser($this, $name, NULL);
		return $this->groupByName[$name];
	}
	
	public function getCurrentUser() {
		$session = SessionManager::getInstance()->getSession($this);
		if (is_numeric($session->user_id));
		return $this->getUserById($session->user_id);
	}
	
	public function viewUser($params, $content) {
		$content = new Content();
		$user = $this->getUserByName($params['user']);
		
		$content->putVariables(array(
				'realName' => $user->getRealName(),
				'userData' => $user,
			));
		$pageData = $this->db->query('SELECT `content` FROM `userpage` WHERE `page` = $page', array(
				'page' => $params['page']
			));
		if (!isset($pageData[0]))
			user_error('The page "'.$params['page'].'" does not exist.');
		return new BlockOutput($this, $content, new Template($pageData[0]['content']));
	}
	public function viewGroup($params, $content) {
		
	}
	public function login($params, $input) {
		if (Core::getUrlManager()->isPost()) {
			$session = SessionManager::getInstance()->getSession($this);
			if ($session->user_id) {
				unset($session->user_id);
			} elseif (Core::getUrlManager()->isInPost('username') && Core::getUrlManager()->isInPost('password')) {
				$user = $this->getUserByName(Core::getUrlManager()->getPost('username'));
				if ($user->checkPassword(Core::getUrlManager()->getPost('password'))) {
					$session->user_id = $user->getId();
					$user->setLastLogin();
				} else
					user_error('Unable to login.');
			}
			$this->redirect('login');
		} else {
			$session = SessionManager::getInstance()->getSession($this);
			$content = new Content();
			$login = new Content();
			$logout = new Content();
			$input = new Content();
			
			if ($session->user_id) {
				$user = $this->getUserById($session->user_id);
				$content->putNamespace('logout', $logout);
				$logout->putNamespace('input', $input);
				$logout->putVariables(array(
						'realName' => $user->getRealName(),
						'lastLogin' => new DateTime('@'.$user->getLastLogin()),
					));
			} else {
				$content->putNamespace('login', $login);
				$login->putNamespace('input', $input);
				$input->putVariables(array(
						'username' => new TextInput('username', ''),
						'password' => new PasswordInput('password'),
					));
			}
			
			return new PostOutput(new BlockOutput($this, $content), $this->mkurl('login'), true);
		}
	}
	public function register($params, $input) {
		if (Core::getUrlManager()->isPost()) {
			if (Core::getUrlManager()->getPost('password') != Core::getUrlManager()->getPost('password2'))
				user_error('The passwords don\'t match.');
			$user = $this->getUserByName(Core::getUrlManager()->getPost('username'));
			if ($user->getId())
				user_error('A user with that name already exists.');
			$user->save();
			$user->setPassword(Core::getUrlManager()->getPost('password'));
			
			$session = SessionManager::getInstance()->getSession($this);
			$session->user_id = $user->getId();
			$this->redirect('login');
		} else {
			$content = new Content();
			$input = new Content();
			$content->putNamespace('input', $input);
			$input->putVariables(array(
					'username' => new TextInput('username', ''),
					'password' => new PasswordInput('password'),
					'password2' => new PasswordInput('password2'),
				));
			return new PostOutput(new BlockOutput($this, $content), $this->mkurl('register'), true);
		}
	}
	
}
