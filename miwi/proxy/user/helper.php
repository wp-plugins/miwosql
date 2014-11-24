<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MUserHelper {

    public static function addUserToGroup($userId, $groupId) {
        // Get the user object.
        // todo: groupId should be rone name like 'administrator', 'editor'

        $new_role = $groupId;

        // update user role using wordpress function
        wp_update_user( array ('ID' => $userId, 'role' => $new_role ) ) ;
        return true;
    }

    public static function getUserGroups($userId) {
        global $wpdb;

        $user = get_userdata($userId);

        $capabilities = $user->{$wpdb->prefix . 'capabilities'};

        return !empty($capabilities) ? $capabilities : array();
    }

    public static function removeUserFromGroup($userId, $groupId) {

        return true;
    }

    public static function setUserGroups($userId, $groups) {

        return true;
    }

    public function getProfile($userId = 0) {
        return null;
    }

    public static function activateUser($activation) {
        return true;
    }

    public static function getUserId($username) {
        $user = get_user_by('login', $username);
        return $user->ID;
    }

    public static function hashPassword($password) {

    }

    public static function verifyPassword($password, $hash, $user_id = 0) {

    }

    public static function getCryptedPassword($plaintext, $salt = '', $encryption = 'md5-hex', $show_encrypt = false) {

    }

    public static function getSalt($encryption = 'md5-hex', $seed = '', $plaintext = '') {

    }

    public static function genRandomPassword($length = 8) {
        return wp_generate_password($length, false);
    }

    protected static function _toAPRMD5($value, $count) {

    }

    private static function _bin($hex) {

    }
}
