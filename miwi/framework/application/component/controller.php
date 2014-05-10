<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MController extends MObject {

    protected $_acoSection;
    protected $_acoSectionValue;
    protected $basePath;
    protected $default_view;
    protected $doTask;
    protected $message;
    protected $messageType;
    protected $methods;
    protected $name;
    protected $model_prefix;
    protected $paths;
    protected $redirect;
    protected $task;
    protected $taskMap;
    protected static $instance;

    public static function addModelPath($path, $prefix = '') {
        mimport('framework.application.component.model');
        MModel::addIncludePath($path, $prefix);
    }

    protected static function createFileName($type, $parts = array()) {
        $filename = '';

        switch ($type) {
            case 'controller':
                if (!empty($parts['format'])) {
                    if ($parts['format'] == 'html') {
                        $parts['format'] = '';
                    }
                    else {
                        $parts['format'] = '.' . $parts['format'];
                    }
                }
                else {
                    $parts['format'] = '';
                }

                $filename = strtolower($parts['name']) . $parts['format'] . '.php';
                break;

            case 'view':
                if (!empty($parts['type'])) {
                    $parts['type'] = '.' . $parts['type'];
                }

                $filename = strtolower($parts['name']) . '/view' . $parts['type'] . '.php';
                break;
        }

        return $filename;
    }

    public static function getInstance($prefix, $config = array()) {
        if (is_object(self::$instance)) {
            return self::$instance;
        }

        // Get the environment configuration.
        $basePath = array_key_exists('base_path', $config) ? $config['base_path'] : MPATH_COMPONENT;
        $format   = MRequest::getWord('format');
        $command  = MRequest::getVar('task', 'display');

        // Check for array format.
        $filter = MFilterInput::getInstance();

        if (is_array($command)) {
            $command = $filter->clean(array_pop(array_keys($command)), 'cmd');
        }
        else {
            $command = $filter->clean($command, 'cmd');
        }

        // Check for a controller.task command.
        if (strpos($command, '.') !== false) {
            // Explode the controller.task command.
            list ($type, $task) = explode('.', $command);

            // Define the controller filename and path.
            $file = self::createFileName('controller', array('name' => $type, 'format' => $format));
            $path = $basePath . '/controllers/' . $file;

            // Reset the task without the controller context.
            MRequest::setVar('task', $task);
        }
        else {
            // Base controller.
            $type = null;
            $task = $command;

            // Define the controller filename and path.
            $file       = self::createFileName('controller', array('name' => 'controller', 'format' => $format));
            $path       = $basePath . '/' . $file;
            $backupfile = self::createFileName('controller', array('name' => 'controller'));
            $backuppath = $basePath . '/' . $backupfile;
        }

        // Get the controller class name.
        $class = ucfirst($prefix) . 'Controller' . ucfirst($type);

        // Include the class if not present.
        if (!class_exists($class)) {
            // If the controller file path exists, include it.
            if (file_exists($path)) {
                require_once $path;
            }
            elseif (isset($backuppath) && file_exists($backuppath)) {
                require_once $backuppath;
            }
            else {
                throw new InvalidArgumentException(MText::sprintf('MLIB_APPLICATION_ERROR_INVALID_CONTROLLER', $type, $format));
            }
        }

        // Instantiate the class.
        if (class_exists($class)) {
            self::$instance = new $class($config);
        }
        else {
            throw new InvalidArgumentException(MText::sprintf('MLIB_APPLICATION_ERROR_INVALID_CONTROLLER_CLASS', $class));
        }

        return self::$instance;
    }

    public function __construct($config = array()) {
        // Initialise variables.
        $this->methods     = array();
        $this->message     = null;
        $this->messageType = 'message';
        $this->paths       = array();
        $this->redirect    = null;
        $this->taskMap     = array();

        if (defined('MDEBUG') && MDEBUG) {
            MLog::addLogger(array('text_file' => 'mcontroller.log.php'), MLog::ALL, array('controller'));
        }

        // Determine the methods to exclude from the base class.
        $xMethods = get_class_methods('MController');

        // Get the public methods in this class using reflection.
        $r        = new ReflectionClass($this);
        $rMethods = $r->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($rMethods as $rMethod) {
            $mName = $rMethod->getName();

            // Add default display method if not explicitly declared.
            if (!in_array($mName, $xMethods) || $mName == 'display') {
                $this->methods[] = strtolower($mName);

                // Auto register the methods as tasks.
                $this->taskMap[strtolower($mName)] = $mName;
            }
        }

        // Set the view name
        if (empty($this->name)) {
            if (array_key_exists('name', $config)) {
                $this->name = $config['name'];
            }
            else {
                $this->name = $this->getName();
            }
        }

        // Set a base path for use by the controller
        if (array_key_exists('base_path', $config)) {
            $this->basePath = $config['base_path'];
        }
        else {
            $this->basePath = MPATH_COMPONENT;
        }

        // If the default task is set, register it as such
        if (array_key_exists('default_task', $config)) {
            $this->registerDefaultTask($config['default_task']);
        }
        else {
            $this->registerDefaultTask('display');
        }

        // Set the models prefix
        if (empty($this->model_prefix)) {
            if (array_key_exists('model_prefix', $config)) {
                // User-defined prefix
                $this->model_prefix = $config['model_prefix'];
            }
            else {
                $this->model_prefix = $this->name . 'Model';
            }
        }

        // Set the default model search path
        if (array_key_exists('model_path', $config)) {
            // User-defined dirs
            $this->addModelPath($config['model_path'], $this->model_prefix);
        }
        else {
            $this->addModelPath($this->basePath . '/models', $this->model_prefix);
        }

        // Set the default view search path
        if (array_key_exists('view_path', $config)) {
            // User-defined dirs
            $this->setPath('view', $config['view_path']);
        }
        else {
            $this->setPath('view', $this->basePath . '/views');
        }

        // Set the default view.
        if (array_key_exists('default_view', $config)) {
            $this->default_view = $config['default_view'];
        }
        elseif (empty($this->default_view)) {
            $this->default_view = $this->getName();
        }

    }

    protected function addPath($type, $path) {
        // Just force path to array
        settype($path, 'array');

        if (!isset($this->paths[$type])) {
            $this->paths[$type] = array();
        }

        // Loop through the path directories
        foreach ($path as $dir) {
            // No surrounding spaces allowed!
            $dir = rtrim(MPath::check($dir, '/'), '/') . '/';

            // Add to the top of the search dirs
            array_unshift($this->paths[$type], $dir);
        }

        return $this;
    }

    public function addViewPath($path) {
        $this->addPath('view', $path);

        return $this;
    }

    public function authorize($task) {
        MLog::add('MController::authorize() is deprecated.', MLog::WARNING, 'deprecated');

        $this->authorise($task);
    }

    public function authorise($task) {
        // Only do access check if the aco section is set
        if ($this->_acoSection) {
            // If we have a section value set that trumps the passed task.
            if ($this->_acoSectionValue) {
                // We have one, so set it and lets do the check
                $task = $this->_acoSectionValue;
            }
            // Get the MUser object for the current user and return the authorization boolean
            $user = MFactory::getUser();

            return $user->authorise($this->_acoSection, $task);
        }
        else {
            // Nothing set, nothing to check... so obviously it's ok :)
            return true;
        }
    }

    protected function checkEditId($context, $id) {
        if ($id) {
            $app    = MFactory::getApplication();
            $values = (array)$app->getUserState($context . '.id');

            $result = in_array((int)$id, $values);

            if (defined('MDEBUG') && MDEBUG) {
                MLog::add(
                    sprintf(
                        'Checking edit ID %s.%s: %d %s',
                        $context,
                        $id,
                        (int)$result,
                        str_replace("\n", ' ', print_r($values, 1))
                    ),
                    MLog::INFO,
                    'controller'
                );
            }

            return $result;
        }
        else {
            // No id for a new item.
            return true;
        }
    }

    protected function createModel($name, $prefix = '', $config = array()) {
        // Clean the model name
        $modelName   = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $classPrefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

        $result = MModel::getInstance($modelName, $classPrefix, $config);

        return $result;
    }

    protected function createView($name, $prefix = '', $type = '', $config = array()) {
        // Clean the view name
        $viewName    = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $classPrefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);
        $viewType    = preg_replace('/[^A-Z0-9_]/i', '', $type);

        // Build the view class name
        $viewClass = $classPrefix . $viewName;

        if (!class_exists($viewClass)) {
            mimport('framework.filesystem.path');
			
            $path = MPath::find($this->paths['view'], $this->createFileName('view', array('name' => $viewName, 'type' => $viewType)));

            if ($path) {
                require_once $path;

                if (!class_exists($viewClass)) {
                    MError::raiseError(500, MText::sprintf('MLIB_APPLICATION_ERROR_VIEW_CLASS_NOT_FOUND', $viewClass, $path));

                    return null;
                }
            }
            else {
                return null;
            }
        }

        return new $viewClass($config);
    }

    public function display($cachable = false, $urlparams = false) {
        $document   = MFactory::getDocument();
        $viewType   = $document->getType();
        $viewName   = MRequest::getCmd('view', $this->default_view);
        $viewLayout = MRequest::getCmd('layout', 'default');

        $view = $this->getView($viewName, $viewType, '', array('base_path' => $this->basePath, 'layout' => $viewLayout));

        // Get/Create the model
        if ($model = $this->getModel($viewName)) {
            // Push the model into the view (as default)
            $view->setModel($model, true);
        }

        $view->document = $document;

        $conf = MFactory::getConfig();

        // Display the view
        if ($cachable && $viewType != 'feed' && $conf->get('caching') >= 1) {
            $option = MRequest::getCmd('option');
            $cache  = MFactory::getCache($option, 'view');

            if (is_array($urlparams)) {
                $app = MFactory::getApplication();

                if (!empty($app->registeredurlparams)) {
                    $registeredurlparams = $app->registeredurlparams;
                }
                else {
                    $registeredurlparams = new stdClass;
                }

                foreach ($urlparams as $key => $value) {
                    // Add your safe url parameters with variable type as value {@see MFilterInput::clean()}.
                    $registeredurlparams->$key = $value;
                }

                $app->registeredurlparams = $registeredurlparams;
            }

            $cache->get($view, 'display');
        }
        else {
            $view->display();
        }

        return $this;
    }

    public function execute($task) {
        $this->task = $task;

        $task = strtolower($task);
        if (isset($this->taskMap[$task])) {
            $doTask = $this->taskMap[$task];
        }
        elseif (isset($this->taskMap['__default'])) {
            $doTask = $this->taskMap['__default'];
        }
        else {
            return MError::raiseError(404, MText::sprintf('MLIB_APPLICATION_ERROR_TASK_NOT_FOUND', $task));
        }

        // Record the actual task being fired
        $this->doTask = $doTask;

        // Make sure we have access
        if ($this->authorise($doTask)) {
            $retval = $this->$doTask();

            return $retval;
        }
        else {
            return MError::raiseError(403, MText::_('MLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'));
        }
    }

    public function getModel($name = '', $prefix = '', $config = array()) {
        if (empty($name)) {
            $name = $this->getName();
        }

        if (empty($prefix)) {
            $prefix = $this->model_prefix;
        }

        if ($model = $this->createModel($name, $prefix, $config)) {
            // Task is a reserved state
            $model->setState('task', $this->task);

            // Let's get the application object and set menu information if it's available
            $app  = MFactory::getApplication();
            $menu = $app->getMenu();

            if (is_object($menu)) {
                if ($item = $menu->getActive()) {
                    $params = $menu->getParams($item->id);
                    // Set default state data
                    $model->setState('parameters.menu', $params);
                }
            }
        }

        return $model;
    }

    public function getName() {
        if (empty($this->name)) {
            $r = null;
            if (!preg_match('/(.*)Controller/i', get_class($this), $r)) {
                MError::raiseError(500, MText::_('MLIB_APPLICATION_ERROR_CONTROLLER_GET_NAME'));
            }
            $this->name = strtolower($r[1]);
        }

        return $this->name;
    }

    public function getTask() {
        return $this->task;
    }

    public function getTasks() {
        return $this->methods;
    }

    public function getView($name = '', $type = '', $prefix = '', $config = array()) {
        static $views;

        if (!isset($views)) {
            $views = array();
        }

        if (empty($name)) {
            $name = $this->getName();
        }

        if (empty($prefix)) {
            $prefix = $this->getName() . 'View';
        }

        if (empty($views[$name])) {
            if ($view = $this->createView($name, $prefix, $type, $config)) {
                $views[$name] = & $view;
            }
            else {
                $result = MError::raiseError(500, MText::sprintf('MLIB_APPLICATION_ERROR_VIEW_NOT_FOUND', $name, $type, $prefix));

                return $result;
            }
        }

        return $views[$name];
    }

    protected function holdEditId($context, $id) {
        // Initialise variables.
        $app    = MFactory::getApplication();
        $values = (array)$app->getUserState($context . '.id');

        // Add the id to the list if non-zero.
        if (!empty($id)) {
            array_push($values, (int)$id);
            $values = array_unique($values);
            $app->setUserState($context . '.id', $values);

            if (defined('MDEBUG') && MDEBUG) {
                MLog::add(
                    sprintf(
                        'Holding edit ID %s.%s %s',
                        $context,
                        $id,
                        str_replace("\n", ' ', print_r($values, 1))
                    ),
                    MLog::INFO,
                    'controller'
                );
            }
        }
    }

    public function redirect() {
        if ($this->redirect) {
            $app = MFactory::getApplication();
            $app->redirect($this->redirect, $this->message, $this->messageType);
        }

        return false;
    }

    public function registerDefaultTask($method) {
        $this->registerTask('__default', $method);

        return $this;
    }

    public function registerTask($task, $method) {
        if (in_array(strtolower($method), $this->methods)) {
            $this->taskMap[strtolower($task)] = $method;
        }

        return $this;
    }

    public function unregisterTask($task) {
        unset($this->taskMap[strtolower($task)]);

        return $this;
    }

    protected function releaseEditId($context, $id) {
        $app    = MFactory::getApplication();
        $values = (array)$app->getUserState($context . '.id');

        // Do a strict search of the edit list values.
        $index = array_search((int)$id, $values, true);

        if (is_int($index)) {
            unset($values[$index]);
            $app->setUserState($context . '.id', $values);

            if (defined('MDEBUG') && MDEBUG) {
                MLog::add(
                    sprintf(
                        'Releasing edit ID %s.%s %s',
                        $context,
                        $id,
                        str_replace("\n", ' ', print_r($values, 1))
                    ),
                    MLog::INFO,
                    'controller'
                );
            }
        }
    }

    public function setAccessControl($section, $value = null) {
        // Deprecation warning.
        MLog::add('MController::setAccessControl() is deprecated.', MLog::WARNING, 'deprecated');
        $this->_acoSection      = $section;
        $this->_acoSectionValue = $value;
    }

    public function setMessage($text, $type = 'message') {
        $previous          = $this->message;
        $this->message     = $text;
        $this->messageType = $type;

        return $previous;
    }

    protected function setPath($type, $path) {
        // Clear out the prior search dirs
        $this->paths[$type] = array();

        // Actually add the user-specified directories
        $this->addPath($type, $path);
    }

    public function setRedirect($url, $msg = null, $type = null) {
        $this->redirect = $url;
        if ($msg !== null) {
            // Controller may have set this directly
            $this->message = $msg;
        }

        // Ensure the type is not overwritten by a previous call to setMessage.
        if (empty($type)) {
            if (empty($this->messageType)) {
                $this->messageType = 'message';
            }
        }
        // If the type is explicitly set, set it.
        else {
            $this->messageType = $type;
        }

        return $this;
    }
}