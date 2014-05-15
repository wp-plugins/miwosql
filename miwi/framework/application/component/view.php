<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MView extends MObject {

    protected $_name = null;
    protected $_models = array();
    protected $_basePath = null;
    protected $_defaultModel = null;
    protected $_layout = 'default';
    protected $_layoutExt = 'php';
    protected $_layoutTemplate = '_';
    protected $_path = array('template' => array(), 'helper' => array());
    protected $_template = null;
    protected $_output = null;
    protected $_escape = 'htmlspecialchars';
    protected $_charset = 'UTF-8';

    public function __construct($config = array()) {
        // Set the view name
        if (empty($this->_name)) {
            if (array_key_exists('name', $config)) {
                $this->_name = $config['name'];
            }
            else {
                $this->_name = $this->getName();
            }
        }

        // Set the charset (used by the variable escaping functions)
        if (array_key_exists('charset', $config)) {
            $this->_charset = $config['charset'];
        }

        // User-defined escaping callback
        if (array_key_exists('escape', $config)) {
            $this->setEscape($config['escape']);
        }

        // Set a base path for use by the view
        if (array_key_exists('base_path', $config)) {
            $this->_basePath = $config['base_path'];
        }
        else {
            $this->_basePath = MPATH_COMPONENT;
        }

        // Set the default template search path
        if (array_key_exists('template_path', $config)) {
            // User-defined dirs
            $this->_setPath('template', $config['template_path']);
        }
        else {
            $this->_setPath('template', $this->_basePath . '/views/' . $this->getName() . '/tmpl');
        }

        // Set the default helper search path
        if (array_key_exists('helper_path', $config)) {
            // User-defined dirs
            $this->_setPath('helper', $config['helper_path']);
        }
        else {
            $this->_setPath('helper', $this->_basePath . '/helpers');
        }

        // Set the layout
        if (array_key_exists('layout', $config)) {
            $this->setLayout($config['layout']);
        }
        else {
            $this->setLayout('default');
        }

        $this->baseurl = MUri::base(true);
		
		MHtml::_('behavior.framework');
	    if (MFactory::getApplication()->isAdmin()) {
		    MHtml::_('behavior.modal');
	    }
    }

    public function display($tpl = null) {
        $result = $this->loadTemplate($tpl);
        if ($result instanceof Exception) {
            return $result;
        }

        echo $result;
    }

    public function escape($var) {
        if (in_array($this->_escape, array('htmlspecialchars', 'htmlentities'))) {
            return call_user_func($this->_escape, $var, ENT_COMPAT, $this->_charset);
        }

        return call_user_func($this->_escape, $var);
    }

    public function get($property, $default = null) {

        // If $model is null we use the default model
        if (is_null($default)) {
            $model = $this->_defaultModel;
        }
        else {
            $model = strtolower($default);
        }

        // First check to make sure the model requested exists
        if (isset($this->_models[$model])) {
            // Model exists, let's build the method name
            $method = 'get' . ucfirst($property);

            // Does the method exist?
            if (method_exists($this->_models[$model], $method)) {
                // The method exists, let's call it and return what we get
                $result = $this->_models[$model]->$method();

                return $result;
            }

        }

        // Degrade to MObject::get
        $result = parent::get($property, $default);

        return $result;
    }

    public function getModel($name = null) {
        if ($name === null) {
            $name = $this->_defaultModel;
        }

        return $this->_models[strtolower($name)];
    }

    public function getLayout() {
        return $this->_layout;
    }

    public function getLayoutTemplate() {
        return $this->_layoutTemplate;
    }

    public function getName() {
        if (empty($this->_name)) {
            $r = null;
            if (!preg_match('/View((view)*(.*(view)?.*))$/i', get_class($this), $r)) {
                MError::raiseError(500, MText::_('MLIB_APPLICATION_ERROR_VIEW_GET_NAME'));
            }
            if (strpos($r[3], "view")) {
                MError::raiseWarning('SOME_ERROR_CODE', MText::_('MLIB_APPLICATION_ERROR_VIEW_GET_NAME_SUBSTRING'));
            }
            $this->_name = strtolower($r[3]);
        }

        return $this->_name;
    }

    public function setModel(&$model, $default = false) {
        $name                 = strtolower($model->getName());
        $this->_models[$name] = $model;

        if ($default) {
            $this->_defaultModel = $name;
        }

        return $model;
    }

    public function setLayout($layout) {
        $previous = $this->_layout;
        if (strpos($layout, ':') === false) {
            $this->_layout = $layout;
        }
        else {
            // Convert parameter to array based on :
            $temp          = explode(':', $layout);
            $this->_layout = $temp[1];

            // Set layout template
            $this->_layoutTemplate = $temp[0];
        }

        return $previous;
    }

    public function setLayoutExt($value) {
        $previous = $this->_layoutExt;
        if ($value = preg_replace('#[^A-Za-z0-9]#', '', trim($value))) {
            $this->_layoutExt = $value;
        }

        return $previous;
    }

    public function setEscape($spec) {
        $this->_escape = $spec;
    }

    public function addTemplatePath($path) {
        $this->_addPath('template', $path);
    }

    public function addHelperPath($path) {
        $this->_addPath('helper', $path);
    }

    public function loadTemplate($tpl = null) {
        // Clear prior output
        $this->_output = null;

        $template       = MFactory::getApplication()->getTemplate();
        $layout         = $this->getLayout();
        $layoutTemplate = $this->getLayoutTemplate();

        // Create the template file name based on the layout
        $file = isset($tpl) ? $layout . '_' . $tpl : $layout;

        // Clean the file name
        $file = preg_replace('/[^A-Z0-9_\.-]/i', '', $file);
        $tpl  = isset($tpl) ? preg_replace('/[^A-Z0-9_\.-]/i', '', $tpl) : $tpl;

        // Load the language file for the template
        $lang = MFactory::getLanguage();
        $lang->load('tpl_' . $template, MPATH_BASE, null, false, true)
        || $lang->load('tpl_' . $template, MPATH_THEMES . "/$template", null, false, true);

        // Change the template folder if alternative layout is in different template
        if (isset($layoutTemplate) && $layoutTemplate != '_' && $layoutTemplate != $template) {
            $this->_path['template'] = str_replace($template, $layoutTemplate, $this->_path['template']);
        }

        // Load the template script
        mimport('framework.filesystem.path');
        $filetofind      = $this->_createFileName('template', array('name' => $file));
        $this->_template = MPath::find($this->_path['template'], $filetofind);

        // If alternate layout can't be found, fall back to default layout
        if ($this->_template == false) {
            $filetofind      = $this->_createFileName('', array('name' => 'default' . (isset($tpl) ? '_' . $tpl : $tpl)));
            $this->_template = MPath::find($this->_path['template'], $filetofind);
        }

        if ($this->_template != false) {
            // Unset so as not to introduce into template scope
            unset($tpl);
            unset($file);

            // Never allow a 'this' property
            if (isset($this->this)) {
                unset($this->this);
            }

            // Start capturing output into a buffer
            ob_start();

            // Include the requested template filename in the local scope
            // (this will execute the view logic).
            include $this->_template;

            // Done with the requested template; get the buffer and
            // clear it.
            $this->_output = ob_get_contents();
            ob_end_clean();

            return $this->_output;
        }
        else {
            return MError::raiseError(500, MText::sprintf('MLIB_APPLICATION_ERROR_LAYOUTFILE_NOT_FOUND', $file));
        }
    }

    public function loadHelper($hlp = null) {
        // Clean the file name
        $file = preg_replace('/[^A-Z0-9_\.-]/i', '', $hlp);

        // Load the template script
        mimport('framework.filesystem.path');
        $helper = MPath::find($this->_path['helper'], $this->_createFileName('helper', array('name' => $file)));

        if ($helper != false) {
            // Include the requested template filename in the local scope
            include_once $helper;
        }
    }

    protected function _setPath($type, $path) {
        $component = MApplicationHelper::getComponentName();
        $app       = MFactory::getApplication();

        // Clear out the prior search dirs
        $this->_path[$type] = array();

        // Actually add the user-specified directories
        $this->_addPath($type, $path);

        // Always add the fallback directories as last resort
        switch (strtolower($type)) {
            case 'template':
                // Set the alternative template search dir
                if (isset($app)) {
                    $component = preg_replace('/[^A-Z0-9_\.-]/i', '', $component);
                    $fallback  = MPATH_THEMES . '/' . $app->getTemplate() . '/html/' . $component . '/' . $this->getName();
                    $this->_addPath('template', $fallback);
                }
                break;
        }
    }

    protected function _addPath($type, $path) {
        // Just force to array
        settype($path, 'array');

        // Loop through the path directories
        foreach ($path as $dir) {
            // no surrounding spaces allowed!
            $dir = trim($dir);

            // Add trailing separators as needed
            if (substr($dir, -1) != DIRECTORY_SEPARATOR) {
                // Directory
                $dir .= DIRECTORY_SEPARATOR;
            }

            // Add to the top of the search dirs
            array_unshift($this->_path[$type], $dir);
        }
    }

    protected function _createFileName($type, $parts = array()) {
        $filename = '';

        switch ($type) {
            case 'template':
                $filename = strtolower($parts['name']) . '.' . $this->_layoutExt;
                break;

            default:
                $filename = strtolower($parts['name']) . '.php';
                break;
        }

        return $filename;
    }
}