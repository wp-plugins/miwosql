<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');
MLoader::import('framework.user.helper');

if ( !function_exists('wp_get_current_user') ) {
    require_once(ABSPATH. 'wp-includes/pluggable.php');
}

class MUser extends MObject {

    protected $isRoot = null;

    public $id = null;

    public $name = null;

    public $username = null;

    public $email = null;

    public $password = null;

    public $password_clear = '';

    public $usertype = null;

    public $block = null;

    public $sendEmail = null;

    public $registerDate = null;

    public $lastvisitDate = null;

    public $activation = null;

    public $params = null;

    public $groups = array();

    public $guest = null;

    public $lastResetTime = null;

    public $resetCount = null;

    protected $_params = null;

    protected $_authGroups = null;

    protected $_authLevels = null;

    protected $_authActions = null;

    protected $_errorMsg = null;

    protected static $instances = array();

    public function __construct($identifier = 0) {
		// Create the user parameters object
		$this->_params = new MRegistry;

		// Load the user if it exists
		if (!empty($identifier))
		{
			$this->load($identifier);
		}
		else
		{
			//initialise
			$this->id = 0;
			$this->sendEmail = 0;
			$this->aid = 0;
			$this->guest = 1;
		}
    }

    public static function getInstance($identifier = 0) {
        // Find the user id
        if(empty($identifier) and function_exists('wp_get_current_user')){
            $user = wp_get_current_user();
            $id = $user->ID;
        }
        else if (!is_numeric($identifier)) {
            if (!$id = MUserHelper::getUserId($identifier)) {
                MError::raiseWarning('SOME_ERROR_CODE', MText::sprintf('MLIB_USER_ERROR_ID_NOT_EXISTS', $identifier));
                $retval = false;
                return $retval;
            }
        }
        else {
            $id = $identifier;
        }

        if (empty(self::$instances[$id])) {
            $user = new MUser($id);
            self::$instances[$id] = $user;
        }

        return self::$instances[$id];
    }

    public function getParam($key, $default = null) {
        return $this->_params->get($key, $default);
    }

    public function setParam($key, $value) {
        return $this->_params->set($key, $value);
    }

    public function defParam($key, $value) {
        return $this->_params->def($key, $value);
    }

    public function authorize($action, $assetname = null) {
        // Deprecation warning.
        MLog::add('MUser::authorize() is deprecated.', MLog::WARNING, 'deprecated');

        return $this->authorise($action, $assetname);
    }

    public function authorise($action, $assetname = null) {
        # rootUser is equal to super admin in wordpress. It setted in load function.

        if($this->isRoot === null and !empty($this->id)) {
            $this->isRoot = is_super_admin( $this->id );
        }

        return $this->isRoot ? true : MAccess::check($this->id, $action, $assetname);
    }

    public function authorisedLevels() {
        // Deprecation warning.
        MLog::add('MUser::authorisedLevels() is deprecated.', MLog::WARNING, 'deprecated');

        return $this->getAuthorisedViewLevels();
    }

    public function getAuthorisedCategories($component, $action) {
        return array();
    }

    public function getAuthorisedViewLevels() {
        if ($this->_authLevels === null) {
            $this->_authLevels = array();
        }

        if (empty($this->_authLevels)) {
            $this->_authLevels = MAccess::getAuthorisedViewLevels($this->id);
        }

        return $this->_authLevels;
    }

    public function getAuthorisedGroups() {
        if ($this->_authGroups === null) {
            $this->_authGroups = array();
        }

        if (empty($this->_authGroups)) {
            $this->_authGroups = MAccess::getGroupsByUser($this->id);
        }

        return $this->_authGroups;
    }

    public function setLastVisit($timestamp = null) {
        return null;
    }

    public function getParameters($loadsetupfile = false, $path = null) {
        return null;
    }

    public function setParameters($params)
    {
        $this->_params = $params;
    }

    public static function getTable($type = null, $prefix = 'MTable') {
        return null;
    }

    public function bind(&$array) {

        return true;
    }

    public function save($updateOnly = false) {

        // Allow an exception to be thrown.
        try {

            // If user is made a Super Admin group and user is NOT a Super Admin
            //
            // @todo ACL - this needs to be acl checked
            //
            $my = MFactory::getUser();

            //are we creating a new user
            $isNew = empty($this->id);

            // If we aren't allowed to create new users return
            if ($isNew && $updateOnly) {
                return true;
            }

            // Get the old user
            $oldUser = new MUser($this->id);

            // Fire the onUserBeforeSave event.
            MPluginHelper::importPlugin('user');
            $dispatcher = MDispatcher::getInstance();

            $result = $dispatcher->trigger('onUserBeforeSave', array($oldUser->getProperties(), $isNew, $this->getProperties()));
            if (in_array(false, $result, true)) {
                // Plugin will have to raise its own error or throw an exception.
                return false;
            }

            //////////////////////////////////////
            //todo:: update old user
            //////////////////////////////////////
            register_new_user($this->username, $this->email);

            // Fire the onUserAfterSave event
            $dispatcher->trigger('onUserAfterSave', array($this->getProperties(), $isNew, $result, $this->getError()));
        } catch (Exception $e) {
            $this->setError($e->getMessage());

            return false;
        }

        return $result;
    }

    public function delete() {
        MPluginHelper::importPlugin('user');

        // Trigger the onUserBeforeDelete event
        $dispatcher = MDispatcher::getInstance();
        $dispatcher->trigger('onUserBeforeDelete', array($this->getProperties()));

        $result = false;
        if (!$result = wp_delete_user($this->id)) {
            $this->setError('Not deleted');
        }

        // Trigger the onUserAfterDelete event
        $dispatcher->trigger('onUserAfterDelete', array($this->getProperties(), $result, $this->getError()));

        return $result;
    }

    public function load($id) {
        $user = get_userdata($id);

        if(!empty($user)){
            $_isRoot = is_super_admin( $user->ID );

            $this->id = $user->ID;
            $this->username = $user->user_login;
            $this->email = $user->user_email;
            $this->name = $user->display_name;
            $this->block = $user->user_status;
            $this->password = $user->user_pass;
            $this->registerDate = $user->user_registered;
            $this->groups = $user->roles;
            $this->isRoot = $_isRoot;
        }

        return true;
    }
}
