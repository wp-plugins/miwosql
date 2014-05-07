<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MClientHelper {

    public static function getCredentials($client, $force = false) {
        static $credentials = array();

        $client = strtolower($client);

        if (!isset($credentials[$client]) || $force) {
            // Initialise variables.
            $config = MFactory::getConfig();

            // Fetch the client layer configuration options for the specific client
            switch ($client) {
                case 'ftp':
                    $options = array(
                        'enabled' => $config->get('ftp_enable'),
                        'host' => $config->get('ftp_host'),
                        'port' => $config->get('ftp_port'),
                        'user' => $config->get('ftp_user'),
                        'pass' => $config->get('ftp_pass'),
                        'root' => $config->get('ftp_root'));
                    break;

                default:
                    $options = array('enabled' => false, 'host' => '', 'port' => '', 'user' => '', 'pass' => '', 'root' => '');
                    break;
            }

            // If user and pass are not set in global config lets see if they are in the session
            if ($options['enabled'] == true && ($options['user'] == '' || $options['pass'] == '')) {
                $session = MFactory::getSession();
                $options['user'] = $session->get($client . '.user', null, 'MClientHelper');
                $options['pass'] = $session->get($client . '.pass', null, 'MClientHelper');
            }

            // If user or pass are missing, disable this client
            if ($options['user'] == '' || $options['pass'] == '') {
                $options['enabled'] = false;
            }

            // Save the credentials for later use
            $credentials[$client] = $options;
        }

        return $credentials[$client];
    }

    public static function setCredentials($client, $user, $pass) {
        $return = false;
        $client = strtolower($client);

        // Test if the given credentials are valid
        switch ($client) {
            case 'ftp':
                $config = MFactory::getConfig();
                $options = array('enabled' => $config->get('ftp_enable'), 'host' => $config->get('ftp_host'), 'port' => $config->get('ftp_port'));

                if ($options['enabled']) {
                    mimport('framework.client.ftp');
                    $ftp = MFTP::getInstance($options['host'], $options['port']);

                    // Test the connection and try to log in
                    if ($ftp->isConnected()) {
                        if ($ftp->login($user, $pass)) {
                            $return = true;
                        }
                        $ftp->quit();
                    }
                }
                break;

            default:
                break;
        }

        if ($return) {
            // Save valid credentials to the session
            $session = MFactory::getSession();
            $session->set($client . '.user', $user, 'MClientHelper');
            $session->set($client . '.pass', $pass, 'MClientHelper');

            // Force re-creation of the data saved within MClientHelper::getCredentials()
            MClientHelper::getCredentials($client, true);
        }

        return $return;
    }

    public static function hasCredentials($client) {
        $return = false;
        $client = strtolower($client);

        // Get (unmodified) credentials for this client
        switch ($client) {
            case 'ftp':
                $config = MFactory::getConfig();
                $options = array('enabled' => $config->get('ftp_enable'), 'user' => $config->get('ftp_user'), 'pass' => $config->get('ftp_pass'));
                break;

            default:
                $options = array('enabled' => false, 'user' => '', 'pass' => '');
                break;
        }

        if ($options['enabled'] == false) {
            // The client is disabled in global config, so let's pretend we are OK
            $return = true;
        }
        elseif ($options['user'] != '' && $options['pass'] != '') {
            // Login credentials are available in global config
            $return = true;
        }
        else {
            // Check if login credentials are available in the session
            $session = MFactory::getSession();
            $user = $session->get($client . '.user', null, 'MClientHelper');
            $pass = $session->get($client . '.pass', null, 'MClientHelper');
            if ($user != '' && $pass != '') {
                $return = true;
            }
        }

        return $return;
    }

    public static function setCredentialsFromRequest($client) {
        // Determine whether FTP credentials have been passed along with the current request
        $user = MRequest::getString('username', null, 'POST', MREQUEST_ALLOWRAW);
        $pass = MRequest::getString('password', null, 'POST', MREQUEST_ALLOWRAW);
        if ($user != '' && $pass != '') {
            // Add credentials to the session
            if (MClientHelper::setCredentials($client, $user, $pass)) {
                $return = false;
            }
            else {
                $return = MError::raiseWarning('SOME_ERROR_CODE', MText::_('MLIB_CLIENT_ERROR_HELPER_SETCREDENTIALSFROMREQUEST_FAILED'));
            }
        }
        else {
            // Just determine if the FTP input fields need to be shown
            $return = !MClientHelper::hasCredentials('ftp');
        }

        return $return;
    }
}
