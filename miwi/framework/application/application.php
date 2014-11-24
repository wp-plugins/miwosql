<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU/GPL v2 or later http://www.gnu.org/copyleft/gpl.html
*/

defined('MIWI') or die('MIWI');

class MApplication extends MObject {

    protected $_clientId = null;
    protected $_messageQueue = array();
    protected $_name = null;
    public $scope = null;
    public $requestTime = null;
    public $startTime = null;
    public $input = null;
    protected static $instances = array();

    public function __construct($config = array()) {
        mimport('framework.error.profiler');

        // Set the view name.
        $this->_name = $this->getName();

        // Only set the clientId if available.
        if (isset($config['clientId'])) {
            $this->_clientId = $config['clientId'];
        }
        else {
			if(defined('DOING_AJAX')){
                if(isset($_GET['client']) and $_GET['client'] == 'admin'){
                    $this->_clientId = 1;
                }
                else{
                    $this->_clientId = 0;
                }

                return;
            }
		
            if (is_admin()) {
                $this->_clientId = 1;
            }
            else {
                $this->_clientId = 0;
            }
        }

        // Enable sessions by default.
        if (!isset($config['session'])) {
            $config['session'] = true;
        }

        // Create the input object
        if (class_exists('MRequest')) {
            $this->input = new MRequest();
        }

        // Set the session default name.
        if (!isset($config['session_name'])) {
            $config['session_name'] = $this->_name;
        }

        // Set the default configuration file.
        if (!isset($config['config_file'])) {
            $config['config_file'] = 'config.php';
        }

        // Create the configuration object.
        if (file_exists(MPATH_CONFIGURATION . '/' . $config['config_file'])) {
            $this->_createConfiguration(MPATH_CONFIGURATION . '/' . $config['config_file']);
        }

        // Create the session if a session name is passed.
		// todo:: use php session 
        if ($config['session'] !== false) {
            $this->_createSession(self::getHash($config['session_name']));
        }

        $this->requestTime = gmdate('Y-m-d H:i');

        // Used by task system to ensure that the system doesn't go over time.
        $this->startTime = MProfiler::getmicrotime();
    }

    public static function getInstance($client, $config = array(), $prefix = 'M') {
        if (empty(self::$instances[$client])) {
            // Create a MApplication object
            $classname = $prefix . 'Application';
            $instance  = new $classname($config);

            self::$instances[$client] = $instance;
        }

        return self::$instances[$client];
    }

    public function initialise($options = array()) {
        if ($this->get('initialised') == true) {
            return;
        }

        // Set the language in the class.
        $config = MFactory::getConfig();

        // Check that we were given a language in the array (since by default may be blank).
        if (isset($options['language'])) {
            $config->set('language', $options['language']);
        }
		
		#for-multi-db
        if(!defined('COOKIEHASH')) {
            wp_cookie_constants();
        }

        // Set user specific editor.
        $user   = MFactory::getUser();
        $editor = $user->getParam('editor', $this->getCfg('editor'));
        if (!MPluginHelper::isEnabled('editors', $editor)) {
            $editor = $this->getCfg('editor');
            if (!MPluginHelper::isEnabled('editors', $editor)) {
                $editor = 'none';
            }
        }

        $config->set('editor', $editor);

        // Trigger the onAfterInitialise event.
        MPluginHelper::importPlugin('system');
        $this->triggerEvent('onAfterInitialise');

        $this->set('initialised', true);
    }

    public function route($component = null) {
        if ($this->get('routed') == true) {
            return;
        }

        $vars = $this->parse($component);

        MRequest::set($vars, 'get', MFactory::getApplication()->isSite() ? true : false);

        // Trigger the onAfterRoute event.
        MPluginHelper::importPlugin('system');
        $this->triggerEvent('onAfterRoute');

        $this->set('routed', true);
    }

    public function parse($component = null) {
        if ($this->get('parsed') == true) {
            $result = $this->get('parsed_vars');
        }
        else {
            if (!empty($component)){
                MRequest::setVar('option', $component);
            }

            $uri = clone MUri::getInstance();

            $router = $this->getRouter();
            $result = $router->parse($uri);

            $this->set('parsed_vars', $result);
            $this->set('parsed', true);
        }

        if (!is_array($result)) {
            $result = array();
        }

        return $result;
    }

    public function dispatch($component = null) {
        if ($this->get('dispatched') == true) {
            return;
        }

        if (empty($component)){
            $component = MRequest::getCmd('option');
        }

        mimport('framework.application.component.helper');

        $output = MComponentHelper::renderComponent($component);

        $this->set('output', $output);

        // Trigger the onAfterDispatch event.
        MPluginHelper::importPlugin('system');
        $this->triggerEvent('onAfterDispatch');

        $this->set('dispatched', true);
    }

    public function render() {
        if ($this->get('rendered') == true) {
            return;
        }

        // Trigger the onBeforeRender event.
        MPluginHelper::importPlugin('system');
        $this->triggerEvent('onBeforeRender');
		
		$output = '';

        $format = MRequest::getCmd('format');

        if ($this->isAdmin() and ($format != 'raw')) {
            $output = MToolBar::getInstance()->render();
        }

        if ($format != 'raw') {
            $output .= MFactory::getDocument()->loadRenderer('message')->render('message');
        }

        $output .= $this->get('output');

        if ($this->isAdmin() and ($format != 'raw')) {
            $output = '<div class="wrap">' . $output. '</div>';
        }

        echo $output;

        // Trigger the onAfterRender event.
        $this->triggerEvent('onAfterRender');

        $this->set('rendered', true);
    }

    public function close($code = 0) {
        exit($code);
    }

    public function redirect($url, $msg = '', $msgType = 'message', $moved = false) {
        // Check for relative internal links.
        if (preg_match('#^index2?\.php#', $url)) {
            $url = MUri::base() . $url;
        }

        // Strip out any line breaks.
        $url = preg_split("/[\r\n]/", $url);
        $url = $url[0];

        // If we don't start with a http we need to fix this before we proceed.
        // We could validly start with something else (e.g. ftp), though this would
        // be unlikely and isn't supported by this API.
        if (!preg_match('#^http#i', $url)) {
            $uri    = MUri::getInstance();
            $prefix = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));

            if ($url[0] == '/') {
                // We just need the prefix since we have a path relative to the root.
                $url = $prefix . $url;
            }
            else {
                // It's relative to where we are now, so lets add that.
                $parts = explode('/', $uri->toString(array('path')));
                array_pop($parts);
                $path = implode('/', $parts) . '/';
                $url  = $prefix . $path . $url;
            }
        }

        // If the message exists, enqueue it.
        if (trim($msg)) {
            $this->enqueueMessage($msg, $msgType);
        }

        // Persist messages if they exist.
        if (count($this->_messageQueue)) {
            $session = MFactory::getSession();
            $session->set('application.queue', $this->_messageQueue);
        }

        // WP redirect
        if ($this->isAdmin()) {
            $option = str_replace('com_', '', MRequest::getCmd('option'));

            $url = str_replace('index.php?', 'admin.php?page='.$option.'&', $url);
        }

        // If the headers have been sent, then we cannot send an additional location header
        // so we will output a javascript redirect statement.
        if (headers_sent()) {
            echo "<script>document.location.href='" . str_replace("'", "&apos;", $url) . "';</script>\n";
        }
        else {
            mimport('phputf8.utils.ascii');
            mimport('framework.environment.browser');
			
            $document = MFactory::getDocument();
            $navigator = MBrowser::getInstance();
			
            if ($navigator->isBrowser('msie') && !utf8_is_ascii($url)) {
                // MSIE type browser and/or server cause issues when url contains utf8 character,so use a javascript redirect method
                echo '<html><head><meta http-equiv="content-type" content="text/html; charset=' . $document->getCharset() . '" />'
                    . '<script>document.location.href=\'' . str_replace("'", "&apos;", $url) . '\';</script></head></html>';
            }
            elseif (!$moved and $navigator->isBrowser('konqueror')) {
                // WebKit browser (identified as konqueror by Joomla!) - Do not use 303, as it causes subresources
                // reload (https://bugs.webkit.org/show_bug.cgi?id=38690)
                echo '<html><head><meta http-equiv="content-type" content="text/html; charset=' . $document->getCharset() . '" />'
                    . '<meta http-equiv="refresh" content="0; url=' . str_replace("'", "&apos;", $url) . '" /></head></html>';
            }
            else {
                // All other browsers, use the more efficient HTTP header method
                header($moved ? 'HTTP/1.1 301 Moved Permanently' : 'HTTP/1.1 303 See other');
                header('Location: ' . $url);
                header('Content-Type: text/html; charset=' . $document->getCharset());
            }
        }
        $this->close();
    }

    public function enqueueMessage($msg, $type = 'message') {
        // For empty queue, if messages exists in the session, enqueue them first.
        if (!count($this->_messageQueue)) {
            $session      = MFactory::getSession();
            $sessionQueue = $session->get('application.queue');

            if (count($sessionQueue)) {
                $this->_messageQueue = $sessionQueue;
                $session->set('application.queue', null);
            }
        }

        // Enqueue the message.
        $this->_messageQueue[] = array('message' => $msg, 'type' => strtolower($type));
    }

    public function getMessageQueue() {
        // For empty queue, if messages exists in the session, enqueue them.
        if (!count($this->_messageQueue)) {
            $session      = MFactory::getSession();
            $sessionQueue = $session->get('application.queue');

            if (count($sessionQueue)) {
                $this->_messageQueue = $sessionQueue;
                $session->set('application.queue', null);
            }
        }

        return $this->_messageQueue;
    }

    public function getCfg($varname, $default = null) {
        $config = MFactory::getConfig();

        return $config->get('' . $varname, $default);
    }

    public function getName() {
        $name = $this->_name;

        if (empty($name)) {
            $r = null;
            if (!preg_match('/M(.*)/i', get_class($this), $r)) {
                MError::raiseError(500, MText::_('MLIB_APPLICATION_ERROR_APPLICATION_GET_NAME'));
            }
            $name = strtolower($r[1]);
        }

        return $name;
    }

    public function getUserState($key, $default = null) {
        $session  = MFactory::getSession();
        $registry = $session->get('registry');

        if (!is_null($registry)) {
            return $registry->get($key, $default);
        }

        return $default;
    }

    public function setUserState($key, $value) {
        $session  = MFactory::getSession();
        $registry = $session->get('registry');

        if (!is_null($registry)) {
            return $registry->set($key, $value);
        }

        return null;
    }

    public function getUserStateFromRequest($key, $request, $default = null, $type = 'none') {
        $cur_state = $this->getUserState($key, $default);
        $new_state = MRequest::getVar($request, null, 'default', $type);

        // Save the new value only if it was set in this request.
        if ($new_state !== null) {
            $this->setUserState($key, $new_state);
        }
        else {
            $new_state = $cur_state;
        }

        return $new_state;
    }

    public static function registerEvent($event, $handler) {
        $dispatcher = MDispatcher::getInstance();
        $dispatcher->register($event, $handler);
    }

    public function triggerEvent($event, $args = null) {
        $dispatcher = MDispatcher::getInstance();

        return $dispatcher->trigger($event, $args);
    }

    public function login($credentials, $options = array()) {
        // Get the global MAuthentication object.
        mimport('framework.user.authentication');

        $authenticate = MAuthentication::getInstance();
        $response     = $authenticate->authenticate($credentials, $options);

        if ($response->status === MAuthentication::STATUS_SUCCESS) {
            // validate that the user should be able to login (different to being authenticated)
            // this permits authentication plugins blocking the user
            $authorisations = $authenticate->authorise($response, $options);
            foreach ($authorisations as $authorisation) {
                $denied_states = array(MAuthentication::STATUS_EXPIRED, MAuthentication::STATUS_DENIED);
                if (in_array($authorisation->status, $denied_states)) {
                    // Trigger onUserAuthorisationFailure Event.
                    $this->triggerEvent('onUserAuthorisationFailure', array((array)$authorisation));

                    // If silent is set, just return false.
                    if (isset($options['silent']) && $options['silent']) {
                        return false;
                    }

                    // Return the error.
                    switch ($authorisation->status) {
                        case MAuthentication::STATUS_EXPIRED:
                            return MError::raiseWarning('102002', MText::_('MLIB_LOGIN_EXPIRED'));
                            break;
                        case MAuthentication::STATUS_DENIED:
                            return MError::raiseWarning('102003', MText::_('MLIB_LOGIN_DENIED'));
                            break;
                        default:
                            return MError::raiseWarning('102004', MText::_('MLIB_LOGIN_AUTHORISATION'));
                            break;
                    }
                }
            }

            // Import the user plugin group.
            MPluginHelper::importPlugin('user');

            // OK, the credentials are authenticated and user is authorised.  Lets fire the onLogin event.
            $results = $this->triggerEvent('onUserLogin', array((array)$response, $options));

            if (!in_array(false, $results, true)) {
                // Set the remember me cookie if enabled.
                if (isset($options['remember']) && $options['remember']) {
                    // Create the encryption key, apply extra hardening using the user agent string.
                    $privateKey = self::getHash(@$_SERVER['HTTP_USER_AGENT']);

                    $key      = new MCryptKey('simple', $privateKey, $privateKey);
                    $crypt    = new MCrypt(new MCryptCipherSimple, $key);
                    $rcookie  = $crypt->encrypt(json_encode($credentials));
                    $lifetime = time() + 365 * 24 * 60 * 60;

                    // Use domain and path set in config for cookie if it exists.
                    $cookie_domain = $this->getCfg('cookie_domain', '');
                    $cookie_path   = $this->getCfg('cookie_path', '/');

                    // Check for SSL connection
                    $secure = ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) || getenv('SSL_PROTOCOL_VERSION'));
                    setcookie(self::getHash('MLOGIN_REMEMBER'), $rcookie, $lifetime, $cookie_path, $cookie_domain, $secure, true);
                }

                return true;
            }
        }

        // Trigger onUserLoginFailure Event.
        $this->triggerEvent('onUserLoginFailure', array((array)$response));

        // If silent is set, just return false.
        if (isset($options['silent']) && $options['silent']) {
            return false;
        }

        // If status is success, any error will have been raised by the user plugin
        if ($response->status !== MAuthentication::STATUS_SUCCESS) {
            MError::raiseWarning('102001', $response->error_message);
        }

        return false;
    }

    public function logout($userid = null, $options = array()) {
        // Get a user object from the MApplication.
        $user = MFactory::getUser($userid);

        // Build the credentials array.
        $parameters['username'] = $user->get('username');
        $parameters['id']       = $user->get('id');

        // Set clientid in the options array if it hasn't been set already.
        if (!isset($options['clientid'])) {
            $options['clientid'] = $this->getClientId();
        }

        // Import the user plugin group.
        MPluginHelper::importPlugin('user');

        // OK, the credentials are built. Lets fire the onLogout event.
        $results = $this->triggerEvent('onUserLogout', array($parameters, $options));

        // Check if any of the plugins failed. If none did, success.

        if (!in_array(false, $results, true)) {
            // Use domain and path set in config for cookie if it exists.
            $cookie_domain = $this->getCfg('cookie_domain', '');
            $cookie_path   = $this->getCfg('cookie_path', '/');
            setcookie(self::getHash('MLOGIN_REMEMBER'), false, time() - 86400, $cookie_path, $cookie_domain);

            return true;
        }

        // Trigger onUserLoginFailure Event.
        $this->triggerEvent('onUserLogoutFailure', array($parameters));

        return false;
    }

    public function getTemplate($params = false) {
        if ($this->isAdmin()) {
            return null;
        }
        $template = new stdClass();
        $template->template = wp_get_theme()->template;
        $template->params = new MRegistry();

        if ($params) {
            return $template;
        }

        return $template->template;
    }

    static public function getRouter($name = null, array $options = array()) {
        if (!isset($name)) {
            $app  = MFactory::getApplication();
            $name = $app->getName();
        }

        $config = MFactory::getConfig();
        $options['mode'] = $config->get('sef');

        mimport('framework.application.router');
        $router = MRouter::getInstance($name, $options);

        if ($router instanceof Exception) {
            return null;
        }

        return $router;
    }

    static public function stringURLSafe($string) {
        if (MFactory::getConfig()->get('unicodeslugs') == 1) {
            $output = MFilterOutput::stringURLUnicodeSlug($string);
        }
        else {
            $output = MFilterOutput::stringURLSafe($string);
        }

        return $output;
    }

    public function getPathway($name = null, $options = array()) {
        if (!isset($name)) {
            $name = $this->_name;
        }

        mimport('framework.application.pathway');
        $pathway = MPathway::getInstance($name, $options);

        if ($pathway instanceof Exception) {
            return null;
        }

        return $pathway;
    }

    public function getMenu($name = null, $options = array()) {
        if (!isset($name)) {
            $name = $this->_name;
        }

        mimport('framework.application.menu');
        $menu = MMenu::getInstance($name, $options);

        if ($menu instanceof Exception) {
            return null;
        }

        return $menu;
    }

    public static function getHash($seed) {
        return md5(MFactory::getConfig()->get('secret') . $seed);
    }

    protected function _createConfiguration($file) {
        MLoader::register('MConfig', $file);

        // Create the MConfig object.
        $config = new MConfig;

        // Get the global configuration object.
        $registry = MFactory::getConfig();

        // Load the configuration values into the registry.
        $registry->loadObject($config);

        return $config;
    }

    protected function _createSession($name) {
	    $options         = array();
	    $options['name'] = $name;
	    $session = MFactory::getSession($options);

	    if ($session->isNew()) {
		    $session->set('registry', new MRegistry('session'));
	    }


	    return $session;
    }

    public function getClientId() {
        return $this->_clientId;
    }

    public function isAdmin() {
        return ($this->_clientId == 1);
    }

    public function isSite() {
        return ($this->_clientId == 0);
    }

    public static function isWinOS() {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function __toString() {
        $compress = $this->getCfg('gzip', false);

        return MResponse::toString($compress);
    }

    /* Site */

    public function getLanguageFilter() {
        //return $this->_language_filter;
        return false;
    }

    public function getParams($option = null) {
        static $params = array();

        $hash = '__default';
        if (!empty($option)) {
            $hash = $option;
        }

        if (!isset($params[$hash])) {
            // Get component parameters
            if (!$option) {
                $option = MRequest::getCmd('option');
            }
            // Get new instance of component global parameters
            $params[$hash] = clone MComponentHelper::getParams($option);
        }

        return $params[$hash];
    }

    public function getPageParameters($option = null) {
        return $this->getParams($option);
    }

    public function setTemplate($template, $styleParams=null) {
        if (is_dir(MPATH_THEMES . '/' . $template)) {
            $this->template = new stdClass();
            $this->template->template = $template;

            if ($styleParams instanceof MRegistry) {
                $this->template->params = $styleParams;
            }
            else {
                $this->template->params = new MRegistry($styleParams);
            }
        }
    }

    public function authorise($itemid) {}

    public function setLanguageFilter($state=false) {
        $old = $this->_language_filter;

        $this->_language_filter = $state;

        return $old;
    }

    public function getDetectBrowser() {
        return $this->_detect_browser;
    }

    public function setDetectBrowser($state = false) {
        $old = $this->_detect_browser;

        $this->_detect_browser = $state;

        return $old;
    }
}