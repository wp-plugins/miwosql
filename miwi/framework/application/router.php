<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

define('MROUTER_MODE_RAW', 0);
define('MROUTER_MODE_SEF', 1);

class MRouter extends MObject {

    protected $mode = null;
    protected $_mode = null;
    protected $vars = array();
    protected $_vars = array();
    protected $rules = array(
        'build' => array(),
        'parse' => array()
    );
    protected $_rules = array(
        'build' => array(),
        'parse' => array()
    );
    protected static $instances = array();

    public function __construct($options = array()) {
        if (array_key_exists('mode', $options)) {
            $this->_mode = $options['mode'];
        }
        else {
            $this->_mode = MROUTER_MODE_RAW;
        }
    }

    public static function getInstance($client, $options = array()) {
        if (empty(self::$instances[$client])) {
            // Load the router object
            $classname = 'MRouter';
            $instance  = new $classname($options);

            self::$instances[$client] = $instance;
        }

        return self::$instances[$client];
    }

    public function parse(&$uri) {
        $vars = array();

        // Get the path
        $path = $uri->getPath();

        // Remove the base URI path.
        $path = substr_replace($path, '', 0, strlen(MUri::base(true)));

        //Set the route
        $uri->setPath(trim($path , '/'));

        // Process the parsed variables based on custom defined rules
        $vars = $this->_processParseRules($uri);

        // Parse RAW URL
        if ($this->_mode == MROUTER_MODE_RAW) {
            $vars += $this->_parseRawRoute($uri);
        }

        // Parse SEF URL
        if ($this->_mode == MROUTER_MODE_SEF) {
            $vars += $this->_parseSefRoute($uri);
        }

        return array_merge($this->getVars(), $vars);
    }

    public function build($url) {
        // Create the URI object
        $uri = $this->_createURI($url);

        // Process the uri information based on custom defined rules
        $this->_processBuildRules($uri);

        // Build RAW URL
        if ($this->_mode == MROUTER_MODE_RAW) {
            $this->_buildRawRoute($uri);
        }

        // Build SEF URL : mysite/route/index.php?var=x
        if ($this->_mode == MROUTER_MODE_SEF) {
            $this->_buildSefRoute($uri);
        }

        return $uri;
    }

    public function getMode() {
        return $this->_mode;
    }

    public function setMode($mode) {
        $this->_mode = $mode;
    }

    public function setVar($key, $value, $create = true) {
        if ($create || array_key_exists($key, $this->_vars)) {
            $this->_vars[$key] = $value;
        }
    }

    public function setVars($vars = array(), $merge = true) {
        if ($merge) {
            $this->_vars = array_merge($this->_vars, $vars);
        }
        else {
            $this->_vars = $vars;
        }
    }

    public function getVar($key) {
        $result = null;
        if (isset($this->_vars[$key])) {
            $result = $this->_vars[$key];
        }

        return $result;
    }

    public function getVars() {
        return $this->_vars;
    }

    public function attachBuildRule($callback) {
        $this->_rules['build'][] = $callback;
    }

    public function attachParseRule($callback) {
        $this->_rules['parse'][] = $callback;
    }

    protected function _parseRawRoute(&$uri) {
        $vars = array();

        $this->setVars($uri->getQuery(true));

        return $vars;
    }

    protected function _parseSefRoute(&$uri) {
        $route = $uri->getPath();

        // Get the variables from the uri
        $vars = $uri->getQuery(true);

        // Handle an empty URL (special case)
        if (empty($route)) {
            // If route is empty AND option is set in the query, assume it's non-sef url, and parse apropriately
            if (isset($vars['option'])) {
                return $this->_parseRawRoute($uri);
            }

            return $vars;
        }

        $segments = explode('/', $route);

        if (!empty($segments[0])) {
            $page = get_page_by_path($segments[0]);

            if (is_object($page)) {
                $vars['page_id'] = $page->ID;
                $vars['option'] = MRequest::getCmd('option');

                array_shift($segments);
            }
        }

        // Set the variables
        $this->setVars($vars);

        if (!empty($route) and isset($this->_vars['option'])) {
            //$segments = explode('/', $route);
            if (empty($segments[0])) {
                array_shift($segments);
            }

            // Handle component	route
            $component = preg_replace('/[^A-Z0-9_\.-]/i', '', $this->_vars['option']);

            // Use the component routing handler if it exists
	        $path = MPATH_WP_PLG.'/'.str_replace('com_', '', $component).'/site/router.php';

            if (file_exists($path) && count($segments)) {
                //decode the route segments
                $segments = $this->_decodeSegments($segments);

                require_once $path;
                $function = str_replace('com_', '', $component).'ParseRoute';
                $function = str_replace(array("-", "."), "", $function);
                $vars = $function($segments);

                $this->setVars($vars);
            }
        }

        return $vars;
    }

    protected function _buildRawRoute(&$uri) {
        $app 	= MFactory::getApplication();
		$option = str_replace('com_', '', $uri->getVar('option'));

        if (!$app->isAdmin()) {
            $option_get = str_replace('com_', '', MRequest::getWord('option', ''));
	        if ($option == $option_get) {
		        $page_id = MRequest::getInt('page_id');
	        }
	        else {
		        $page_id = null;
	        }

            if(empty($page_id) and !empty($option)) {
                $page_id = MFactory::getWOption($option.'_page_id');
            }

            if (!empty($page_id)) {
                $uri->setVar('page_id', $page_id);
            }
        }
        else {
            $format = $uri->getVar('format');

            $url = $uri->get('_uri');

            if ($format == 'raw') {
                $url = str_replace('index.php?', MURL_ADMIN.'/admin-ajax.php?client=admin&action='.$option.'&page='.$option.'&', $url);
            }
            else {
                if ($option == 'config') {
                    $page = str_replace('com_', '', $uri->getVar('component'));

                    $url = 'admin.php?page='.$page.'&option=com_'.$page.'&view=config';
                }
                else {
                    $url = str_replace('index.php?', 'admin.php?page='.$option.'&', $url);
                }
            }

            $uri = MUri::getInstance($url);
        }
    }

    protected function _buildSefRoute(&$uri) {
        // Get the route
        $route = $uri->getPath();

        $route = str_replace('index.php', '', $route);

        // Get the query data
        $query = $uri->getQuery(true);

        if (!isset($query['option'])) {
            return;
        }

        /*
         * Build the component route
         */
        $component	= preg_replace('/[^A-Z0-9_\.-]/i', '', $query['option']);
        $tmp		= '';

        // Use the component routing handler if it exists
        $path = MPATH_WP_PLG.'/' . str_replace('com_', '', $component) . '/site/router.php';

        // Use the custom routing handler if it exists
        if (file_exists($path) && !empty($query)) {
            require_once $path;
            $function	= str_replace('com_', '', $component).'BuildRoute';
            $function   = str_replace(array("-", "."), "", $function);
            $parts		= $function($query);

            // encode the route segments
            $parts = $this->_encodeSegments($parts);

            $result = implode('/', $parts);
            $tmp	= ($result != "") ? $result : '';
        }

        /*
         * Build the application route
         */
        if ($tmp) {
            $route .= '/'.$tmp;
        }
        elseif ($route=='index.php') {
            $route = '';
        }

        $option_get = str_replace('com_', '', MRequest::getWord('option', ''));
		if (str_replace('com_', '', $component) == $option_get) {
			$page_id = MRequest::getInt('page_id');
		}
		else {
			$page_id = null;
		}
			
        if (empty($page_id)) {
            $page_id = MFactory::getWOption(str_replace('com_', '', $query['option']).'_page_id');
        }

        if (!empty($page_id)) {
            $post = MFactory::getWPost($page_id);

            if (is_object($post)) {
                $route = $post->post_name . '/' . ltrim($route, '/');
            }

            $route = MUri::base(true).'/'.ltrim($route, '/');
        }
        // Unset unneeded query information
        unset($query['option']);

        //Set query again in the URI
        $uri->setQuery($query);
        $uri->setPath($route);
    }

    protected function _processParseRules(&$uri) {
        $vars = array();

        foreach ($this->_rules['parse'] as $rule) {
            $vars += call_user_func_array($rule, array(&$this, &$uri));
        }

        // Process the pagination support
        if ($this->_mode == MROUTER_MODE_SEF) {
            if ($start = $uri->getVar('start')) {
                $uri->delVar('start');
                $vars['limitstart'] = $start;
            }
        }

        return $vars;
    }

    protected function _processBuildRules(&$uri) {
        foreach ($this->_rules['build'] as $rule) {
            call_user_func_array($rule, array(&$this, &$uri));
        }

        // Get the path data
        $route = $uri->getPath();

        if ($this->_mode == MROUTER_MODE_SEF && $route) {
            if ($limitstart = $uri->getVar('limitstart')) {
                $uri->setVar('start', (int) $limitstart);
                $uri->delVar('limitstart');
            }
        }

        $uri->setPath($route);
    }

    protected function _createURI($url) {
        // Create full URL if we are only appending variables to it
        if (substr($url, 0, 1) == '&') {
            $vars = array();
            if (strpos($url, '&amp;') !== false) {
                $url = str_replace('&amp;', '&', $url);
            }

            parse_str($url, $vars);

            $vars = array_merge($this->getVars(), $vars);

            foreach ($vars as $key => $var) {
                if ($var == "") {
                    unset($vars[$key]);
                }
            }

            $url = 'index.php?' . MUri::buildQuery($vars);
        }

        // Decompose link into url component parts
        return new MUri($url);
    }

    protected function _encodeSegments($segments) {
        $total = count($segments);
        for ($i = 0; $i < $total; $i++) {
            $segments[$i] = str_replace(':', '-', $segments[$i]);
        }

        return $segments;
    }

    protected function _decodeSegments($segments) {
        $total = count($segments);
        for ($i = 0; $i < $total; $i++) {
            $segments[$i] = preg_replace('/-/', ':', $segments[$i], 1);
        }

        return $segments;
    }
}