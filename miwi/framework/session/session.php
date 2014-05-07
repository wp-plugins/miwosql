<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/
defined('MIWI') or die('MIWI');
class MSession extends MObject {

	protected $_state = 'active';

	protected $_expire = 15;

	protected $_store = null;

	protected $_security = array ( 'fix_browser' );

	protected $_force_ssl = false;

	protected static $instance;

	public function __construct($store = 'none', $options = array()) {
		// Need to destroy any existing sessions started with session.auto_start
		if (session_id()) {
			session_unset();
			session_destroy();
		}
		
		// Set default sessios save handler
		ini_set('session.save_handler', 'files');
		
		// Disable transparent sid support
		ini_set('session.use_trans_sid', '0');
		
		// Create handler
		// $this->_store = MSessionStorage::getInstance($store, $options);
		

		// Set options
		$this->_setOptions($options);
		
		// $this->_setCookieParams();
		

		// Load the session
		$this->_start();
		
		// Initialise the session
		$this->_setCounter();
		$this->_setTimers();
		
		$this->_state = 'active';
		
		// Perform security checks
		$this->_validate();
	}

	public function __destruct() {
		$this->close();
	}

	public static function getInstance($handler, $options) {
		if (! is_object(self::$instance)) {
			self::$instance = new MSession($handler, $options);
		}
		
		return self::$instance;
	}

	public function getState() {
		return $this->_state;
	}

	public function getExpire() {
		return $this->_expire;
	}

	public function getToken($forceNew = false) {
		$token = $this->get('session.token');
		
		// Create a token
		if ($token === null || $forceNew) {
			$token = $this->_createToken(12);
			$this->set('session.token', $token);
		}
		
		return $token;
	}

	public function hasToken($tCheck, $forceExpire = true) {
		// Check if a token exists in the session
		$tStored = $this->get('session.token');
		
		// Check token
		if (($tStored !== $tCheck)) {
			if ($forceExpire) {
				$this->_state = 'expired';
			}
			return false;
		}
		
		return true;
	}

	public static function getFormToken($forceNew = false) {
		$user = MFactory::getUser();
		$session = MFactory::getSession();
		$hash = MApplication::getHash($user->get('id', 0) . $session->getToken($forceNew));
		
		return $hash;
	}

	public static function checkToken($method = 'post') {
		if ($method == 'default') {
			trigger_error("MSession::checkToken() doesn't support 'default' for the method parameter.", E_USER_ERROR);
			return false;
		}
		
		$token = self::getFormToken();
		$app = MFactory::getApplication();
		
		if (! MRequest::getVar($token, '', $method, 'alnum')) {
			$session = MFactory::getSession();
			if ($session->isNew()) {
				// Redirect to login screen.
				$app->redirect(MRoute::_('index.php'), MText::_('MLIB_ENVIRONMENT_SESSION_EXPIRED'));
				$app->close();
			}
			else {
				return false;
			}
		}
		else {
			return true;
		}
	}

	public function getName() {
		if ($this->_state === 'destroyed') {
			// @TODO : raise error
			return null;
		}
		return session_name();
	}

	public function getId() {
		if ($this->_state === 'destroyed') {
			// @TODO : raise error
			return null;
		}
		return session_id();
	}

	public static function getStores() {
		return $names = NULL;
	}

	public function isNew() {
		$counter = $this->get('session.counter');
		if ($counter === 1) {
			return true;
		}
		return false;
	}

	public function get($name, $default = null, $namespace = 'default') {
		// Add prefix to namespace to avoid collisions
		$namespace = '__' . $namespace;
		
		if ($this->_state !== 'active' && $this->_state !== 'expired') {
			// @TODO :: generated error here
			$error = null;
			return $error;
		}
		
		if (isset($_SESSION[$namespace][$name])) {
			return $_SESSION[$namespace][$name];
		}
		return $default;
	}

	public function set($name, $value = null, $namespace = 'default') {
		// Add prefix to namespace to avoid collisions
		$namespace = '__' . $namespace;
		
		if ($this->_state !== 'active') {
			// @TODO :: generated error here
			return null;
		}
		
		$old = isset($_SESSION[$namespace][$name]) ? $_SESSION[$namespace][$name] : null;
		
		if (null === $value) {
			unset($_SESSION[$namespace][$name]);
		}
		else {
			$_SESSION[$namespace][$name] = $value;
		}
		
		return $old;
	}

	public function has($name, $namespace = 'default') {
		// Add prefix to namespace to avoid collisions.
		$namespace = '__' . $namespace;
		
		if ($this->_state !== 'active') {
			// @TODO :: generated error here
			return null;
		}
		
		return isset($_SESSION[$namespace][$name]);
	}

	public function clear($name, $namespace = 'default') {
		// Add prefix to namespace to avoid collisions
		$namespace = '__' . $namespace;
		
		if ($this->_state !== 'active') {
			// @TODO :: generated error here
			return null;
		}
		
		$value = null;
		if (isset($_SESSION[$namespace][$name])) {
			$value = $_SESSION[$namespace][$name];
			unset($_SESSION[$namespace][$name]);
		}
		
		return $value;
	}

	protected function _start() {
		// Start session if not started
		if ($this->_state == 'restart') {
			session_id($this->_createId());
		}
		else {
			$session_name = session_name();
			if (! MRequest::getVar($session_name, false, 'COOKIE')) {
				if (MRequest::getVar($session_name)) {
					session_id(MRequest::getVar($session_name));
					setcookie($session_name, '', time() - 3600);
				}
			}
		}
		
		session_cache_limiter('none');
		session_start();
		
		return true;
	}

	public function destroy() {
		// Session was already destroyed
		if ($this->_state === 'destroyed') {
			return true;
		}

		session_unset();
		session_destroy();
		
		$this->_state = 'destroyed';
		return true;
	}

	public function restart() {
		$this->destroy();
		if ($this->_state !== 'destroyed') {
			// @TODO :: generated error here
			return false;
		}
		
		// Re-register the session handler after a session has been destroyed, to avoid PHP bug
		//$this->_store->register();
		
		$this->_state = 'restart';
		
		// Regenerate session id
		$id = $this->_createId();
		session_id($id);
		$this->_start();
		$this->_state = 'active';
		
		$this->_validate();
		$this->_setCounter();
		
		return true;
	}

	public function fork() {
		if ($this->_state !== 'active') {
			// @TODO :: generated error here
			return false;
		}
		
		// Save values
		$values = $_SESSION;
		
		// Keep session config
		$trans = ini_get('session.use_trans_sid');
		if ($trans) {
			ini_set('session.use_trans_sid', 0);
		}
		$cookie = session_get_cookie_params();
		
		// Create new session id
		$id = $this->_createId();
		
		// Kill session
		session_destroy();
		
		// Re-register the session store after a session has been destroyed, to avoid PHP bug
		//$this->_store->register();
		
		// Restore config
		ini_set('session.use_trans_sid', $trans);
		session_set_cookie_params($cookie['lifetime'], $cookie['path'], $cookie['domain'], $cookie['secure']);
		
		// Restart session with new id
		session_id($id);
		session_start();
		
		return true;
	}

	public function close() {
		session_write_close();
	}

	protected function _createId() {
		$id = 0;
		while (strlen($id) < 32) {
			$id .= mt_rand(0, mt_getrandmax());
		}
		
		$id = md5(uniqid($id, true));
		return $id;
	}

	protected function _setCookieParams() {}

	protected function _createToken($length = 32) {
		static $chars = '0123456789abcdef';
		$max = strlen($chars) - 1;
		$token = '';
		$name = session_name();
		for ($i = 0; $i < $length; ++ $i) {
			$token .= $chars[(rand(0, $max))];
		}
		
		return md5($token . $name);
	}

	protected function _setCounter() {
		$counter = $this->get('session.counter', 0);
		++ $counter;
		
		$this->set('session.counter', $counter);
		return true;
	}

	protected function _setTimers() {
		if (! $this->has('session.timer.start')) {
			$start = time();
			
			$this->set('session.timer.start', $start);
			$this->set('session.timer.last', $start);
			$this->set('session.timer.now', $start);
		}
		
		$this->set('session.timer.last', $this->get('session.timer.now'));
		$this->set('session.timer.now', time());
		
		return true;
	}

	protected function _setOptions(&$options) {
		// Set name
		if (isset($options['name'])) {
			session_name(md5($options['name']));
		}
		
		// Set id
		if (isset($options['id'])) {
			session_id($options['id']);
		}
		
		// Set expire time
		if (isset($options['expire'])) {
			$this->_expire = $options['expire'];
		}
		
		// Get security options
		if (isset($options['security'])) {
			$this->_security = explode(',', $options['security']);
		}
		
		if (isset($options['force_ssl'])) {
			$this->_force_ssl = (bool) $options['force_ssl'];
		}
		
		// Sync the session maxlifetime
		ini_set('session.gc_maxlifetime', $this->_expire);
		
		return true;
	}

	protected function _validate($restart = false) {
		// Allow to restart a session
		if ($restart) {
			$this->_state = 'active';
			
			$this->set('session.client.address', null);
			$this->set('session.client.forwarded', null);
			$this->set('session.client.browser', null);
			$this->set('session.token', null);
		}
		
		// Check if session has expired
		if ($this->_expire) {
			$curTime = $this->get('session.timer.now', 0);
			$maxTime = $this->get('session.timer.last', 0) + $this->_expire;
			
			// Empty session variables
			if ($maxTime < $curTime) {
				$this->_state = 'expired';
				return false;
			}
		}
		
		// Record proxy forwarded for in the session in case we need it later
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->set('session.client.forwarded', $_SERVER['HTTP_X_FORWARDED_FOR']);
		}
		
		// Check for client address
		if (in_array('fix_adress', $this->_security) && isset($_SERVER['REMOTE_ADDR'])) {
			$ip = $this->get('session.client.address');
			
			if ($ip === null) {
				$this->set('session.client.address', $_SERVER['REMOTE_ADDR']);
			}
			elseif ($_SERVER['REMOTE_ADDR'] !== $ip) {
				$this->_state = 'error';
				return false;
			}
		}
		
		// Check for clients browser
		if (in_array('fix_browser', $this->_security) && isset($_SERVER['HTTP_USER_AGENT'])) {
			$browser = $this->get('session.client.browser');
			
			if ($browser === null) {
				$this->set('session.client.browser', $_SERVER['HTTP_USER_AGENT']);
			}
			elseif ($_SERVER['HTTP_USER_AGENT'] !== $browser) {
				// @todo remove code: 				$this->_state	=	'error';
				// @todo remove code: 				return false;
			}
		}
		
		return true;
	}
}