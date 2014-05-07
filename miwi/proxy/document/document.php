<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MLoader::register('MDocumentRenderer', dirname(__FILE__) . '/renderer.php');
mimport('framework.environment.response');
mimport('framework.filter.filteroutput');

class MDocument extends MObject {

    public $title		 		= '';
    public $description	 		= '';
    public $link		 		= '';
    public $base				= '';
    public $language	 		= 'en-gb';
    public $direction 	 		= 'ltr';
    public $_generator 	 		= 'Miwisoft Framework';
    public $_mdate		 		= '';
    public $_tab		 		= "\11";
    public $_lineEnd 	 		= "\12";
    public $_charset 	 		= 'utf-8';
    public $_mime 		 		= '';
    public $_namespace	 		= '';
    public $_profile 	 		= '';
    public $_scripts 	 		= array();
    public $_script		 		= array();
    public $_styleSheets 		= array();
    public $_style 		 		= array();
    public $_metaTags 			= array();
    public $_engine		        = null;
    public $_type 				= null;
    public static $_buffer 		= null;
    protected static $instances = array();
	
    public function __construct($options = array()) {
        parent::__construct();

        if (array_key_exists('lineend', $options)) {
            $this->setLineEnd($options['lineend']);
        }

        if (array_key_exists('charset', $options)) {
            $this->setCharset($options['charset']);
        }

        if (array_key_exists('language', $options)) {
            $this->setLanguage($options['language']);
        }

        if (array_key_exists('direction', $options)) {
            $this->setDirection($options['direction']);
        }

        if (array_key_exists('tab', $options)) {
            $this->setTab($options['tab']);
        }

        if (array_key_exists('link', $options)) {
            $this->setLink($options['link']);
        }

        if (array_key_exists('base', $options)) {
            $this->setBase($options['base']);
        }
    }

    public static function getInstance($type = 'html', $attributes = array()) {
        $signature = serialize(array($type, $attributes));

        if (empty(self::$instances[$signature])) {
            $type = preg_replace('/[^A-Z0-9_\.-]/i', '', $type);
            $path_p = MPATH_MIWI . '/proxy/document/' . $type . '/' . $type . '.php';
            $path_f = MPATH_MIWI . '/framework/document/' . $type . '/' . $type . '.php';
            $ntype = null;

            $path = $path_f;
            if(file_exists($path_p)) {
                $path = $path_p;
            }

            // Check if the document type exists
            if (!file_exists($path)) {
                // Default to the raw format
                $ntype = $type;
                $type = 'raw';
            }

            // Determine the path and class
            $class = 'MDocument' . $type;
            if (!class_exists($class)) {
                //$path = dirname(__FILE__) . '/' . $type . '/' . $type . '.php';
                if (file_exists($path)) {
                    require_once $path;
                }
                else {
                    MError::raiseError(500, MText::_('MLIB_DOCUMENT_ERROR_UNABLE_LOAD_DOC_CLASS'));
                }
            }

            $instance = new $class($attributes);
            self::$instances[$signature] = & $instance;

            if (!is_null($ntype)) {
                // Set the type to the Document type originally requested
                $instance->setType($ntype);
            }
        }

        return self::$instances[$signature];
    }

    public function setType($type) {
        $this->_type = $type;

        return $this;
    }

    public function getType() {
        return $this->_type;
    }

    public function getBuffer() {
        return self::$_buffer;
    }

    public function setBuffer($content, $options = array()) {
        self::$_buffer = $content;

        return $this;
    }

    public function getMetaData($name, $httpEquiv = false) {
        $result = '';
        $name = strtolower($name);
        if ($name == 'generator') {
            $result = $this->getGenerator();
        }
        elseif ($name == 'description') {
            $result = $this->getDescription();
        }
        else {
            if ($httpEquiv == true) {
                $result = @$this->_metaTags['http-equiv'][$name];
            }
            else {
                $result = @$this->_metaTags['standard'][$name];
            }
        }

        return $result;
    }

    public function setMetaData($name, $content, $http_equiv = false, $sync = true) {
        $name = strtolower($name);

        if ($name == 'generator') {
            $this->setGenerator($content);
        }
        elseif ($name == 'description') {
            $this->setDescription($content);
        }
        else {
            if ($http_equiv == true) {
                $this->_metaTags['http-equiv'][$name] = $content;

                // Syncing with HTTP-header
                if ($sync && strtolower($name) == 'content-type') {
                    $this->setMimeEncoding($content, false);
                }
            }
            else {
                $this->_metaTags['standard'][$name] = $content;
            }
        }

        return $this;
    }

    public function addScript($url, $type = "text/javascript", $defer = false, $async = false) {
        $this->_scripts[$url] = $url;
		
		return $this;
    }

    public function addScriptDeclaration($content, $type = 'text/javascript') {
        $handle = md5(mt_rand());

        $this->_script[$handle] = $content;

        return $this;
    }

    public function addStyleSheet($url, $type = 'text/css', $media = null, $attribs = array()) {
        $this->_styleSheets[$url] = $url;

        return $this;
    }

    public function addStyleDeclaration($content, $type = 'text/css') {
        $handle = md5(mt_rand());

        $this->_style[$handle] = $content;

        return $this;
    }

    public function setCharset($type = 'utf-8') {
        $this->_charset = $type;

        return $this;
    }

    public function getCharset() {
        return $this->_charset;
    }

    public function setLanguage($lang = "en-gb") {
        $this->language = strtolower($lang);

        return $this;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function setDirection($dir = "ltr") {
        $this->direction = strtolower($dir);

        return $this;
    }

    public function getDirection() {
        return $this->direction;
    }

	public function setTitle($title) {
		$this->title = $title;
		add_filter('wp_title', array($this, '_set_wp_title'));
		return $this;
	}

	public function _set_wp_title($title) {
		if (!empty($this->title)) {
			return $this->title.' | '.get_bloginfo('description');
		}

		if (empty($title) && (is_home() || is_front_page())) {
			return __('Home', 'theme_domain').' | '.get_bloginfo('description');
		}
		return $title;
	}

    public function getTitle() {
        get_the_title();
    }

    public function setBase($base) {
        $this->base = $base;

        return $this;
    }

    public function getBase() {
        return $this->base;
    }

    public function setDescription($description) {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setLink($url) {
        $this->link = $url;

        return $this;
    }

    public function getLink() {
        return $this->link;
    }

    public function setGenerator($generator) {
        $this->_generator = $generator;

        return $this;
    }

    public function getGenerator() {
        return $this->_generator;
    }

    public function setModifiedDate($date) {
        $this->_mdate = $date;

        return $this;
    }

    public function getModifiedDate() {
        return $this->_mdate;
    }

    public function setMimeEncoding($type = 'text/html', $sync = true) {
        $this->_mime = strtolower($type);

        // Syncing with meta-data
        if ($sync) {
            $this->setMetaData('content-type', $type, true, false);
        }

        return $this;
    }

    public function getMimeEncoding() {
        return $this->_mime;
    }

    public function setLineEnd($style) {
        switch ($style) {
            case 'win':
                $this->_lineEnd = "\15\12";
                break;
            case 'unix':
                $this->_lineEnd = "\12";
                break;
            case 'mac':
                $this->_lineEnd = "\15";
                break;
            default:
                $this->_lineEnd = $style;
        }

        return $this;
    }

    public function _getLineEnd() {
        return $this->_lineEnd;
    }

    public function setTab($string) {
        $this->_tab = $string;

        return $this;
    }

    public function _getTab() {
        return $this->_tab;
    }

    public function loadRenderer($type) {
        $class = 'MDocumentRenderer' . $type;

        if (!class_exists($class)) {
            $path_p = MPATH_MIWI . '/proxy/document/' . $this->_type . '/renderer/' . $type . '.php';
            $path_f = MPATH_MIWI . '/framework/document/' . $this->_type . '/renderer/' . $type . '.php';

            $path = $path_f;
            if(file_exists($path_p)) {
                $path = $path_p;
            }

            if (file_exists($path)) {
                require_once $path;
            }
            else {
                MError::raiseError(500, MText::_('Unable to load renderer class'));
            }
        }


        if (!class_exists($class)) {
            return null;
        }

        $instance = new $class($this);

        return $instance;
    }

    public function parse($params = array()) {
        return $this;
    }

    public function render($cache = false, $params = array()) {
        if ($mdate = $this->getModifiedDate()) {
            MResponse::setHeader('Last-Modified', $mdate /* gmdate('D, d M Y H:i:s', time() + 900) . ' GMT' */);
        }

        MResponse::setHeader('Content-Type', $this->_mime . ($this->_charset ? '; charset=' . $this->_charset : ''));
    }
}
