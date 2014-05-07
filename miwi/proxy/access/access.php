<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.utilities.arrayhelper');

class MAccess {

    protected static $viewLevels = array();

    protected static $assetRules = array();

    protected static $userGroups = array();

    protected static $userGroupPaths = array();

    protected static $groupsByUser = array();

    public static function clearStatics() {
        self::$viewLevels = array();
        self::$assetRules = array();
        self::$userGroups = array();
        self::$userGroupPaths = array();
        self::$groupsByUser = array();
    }

    public static function check($userId, $action, $asset = null) {
        $result = false;

        switch($action) {
            case 'core.admin':
            case 'core.manage':
                $_isRoot = is_super_admin( $userId );
                if(!empty($_isRoot)){
                    return true;
                }
                break;
            case 'core.create':
            case 'core.edit':
            case 'core.edit.own':
                $action = 'post_edit';
                break;
            case 'core.edit.state':
                $action = 'post_publish';
                break;
            case 'core.delete':
                $action = 'post_delete';
                break;
            default;
                break;
        }

        $result = user_can($userId, $action);

        return $result;
    }

    public static function getAuthorisedViewLevels($userId) {
        return array(0,1,2,3,4,5,6,7,8,9);
    }

    public static function checkGroup($groupId, $action, $asset = null) {}

    protected static function getGroupPath($groupId) {}

    public static function getAssetRules($asset, $recursive = false) {}

    public static function getGroupsByUser($userId, $recursive = true) {}

    public static function getUsersByGroup($groupId, $recursive = false) {}

    public static function getActions($component, $section = 'component') {}

    public static function getActionsFromFile($file, $xpath = "/access/section[@name='component']/") {}

    public static function getActionsFromData($data, $xpath = "/access/section[@name='component']/") {}
}
