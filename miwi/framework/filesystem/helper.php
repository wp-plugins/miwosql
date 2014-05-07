<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.file');
mimport('framework.filesystem.folder');

class MFilesystemHelper {

    public static function remotefsize($url) {
        $sch = parse_url($url, PHP_URL_SCHEME);

        if (($sch != 'http') && ($sch != 'https') && ($sch != 'ftp') && ($sch != 'ftps')) {
            return false;
        }

        if (($sch == 'http') || ($sch == 'https')) {
            $headers = get_headers($url, 1);

            if ((!array_key_exists('Content-Length', $headers))) {
                return false;
            }

            return $headers['Content-Length'];
        }

        if (($sch == 'ftp') || ($sch == 'ftps')) {
            $server = parse_url($url, PHP_URL_HOST);
            $port = parse_url($url, PHP_URL_PORT);
            $path = parse_url($url, PHP_URL_PATH);
            $user = parse_url($url, PHP_URL_USER);
            $pass = parse_url($url, PHP_URL_PASS);

            if ((!$server) || (!$path)) {
                return false;
            }

            if (!$port) {
                $port = 21;
            }

            if (!$user) {
                $user = 'anonymous';
            }

            if (!$pass) {
                $pass = '';
            }

            switch ($sch) {
                case 'ftp':
                    $ftpid = ftp_connect($server, $port);
                    break;

                case 'ftps':
                    $ftpid = ftp_ssl_connect($server, $port);
                    break;
            }

            if (!$ftpid) {
                return false;
            }

            $login = ftp_login($ftpid, $user, $pass);

            if (!$login) {
                return false;
            }

            $ftpsize = ftp_size($ftpid, $path);
            ftp_close($ftpid);

            if ($ftpsize == -1) {
                return false;
            }

            return $ftpsize;
        }
    }

    public static function ftpChmod($url, $mode) {
        $sch = parse_url($url, PHP_URL_SCHEME);

        if (($sch != 'ftp') && ($sch != 'ftps')) {
            return false;
        }

        $server = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $path = parse_url($url, PHP_URL_PATH);
        $user = parse_url($url, PHP_URL_USER);
        $pass = parse_url($url, PHP_URL_PASS);

        if ((!$server) || (!$path)) {
            return false;
        }

        if (!$port) {
            $port = 21;
        }

        if (!$user) {
            $user = 'anonymous';
        }

        if (!$pass) {
            $pass = '';
        }

        switch ($sch) {
            case 'ftp':
                $ftpid = ftp_connect($server, $port);
                break;

            case 'ftps':
                $ftpid = ftp_ssl_connect($server, $port);
                break;
        }

        if (!$ftpid) {
            return false;
        }

        $login = ftp_login($ftpid, $user, $pass);

        if (!$login) {
            return false;
        }

        $res = ftp_chmod($ftpid, $mode, $path);
        ftp_close($ftpid);

        return $res;
    }

    public static function getWriteModes() {
        return array('w', 'w+', 'a', 'a+', 'r+', 'x', 'x+');
    }

    public static function getSupported() {
        // Really quite cool what php can do with arrays when you let it...
        static $streams;

        if (!$streams) {
            $streams = array_merge(stream_get_wrappers(), MFilesystemHelper::getMStreams());
        }

        return $streams;
    }

    public static function getTransports() {
        // Is this overkill?
        return stream_get_transports();
    }

    public static function getFilters() {
        // Note: This will look like the getSupported() function with J! filters.
        // TODO: add user space filter loading like user space stream loading
        return stream_get_filters();
    }

    public static function getMStreams() {
        static $streams;

        if (!$streams) {
            $streams = array_map(array('MFile', 'stripExt'), MFolder::files(dirname(__FILE__) . '/streams', '.php'));
        }

        return $streams;
    }

    public static function isMiwiStream($streamname) {
        return in_array($streamname, MFilesystemHelper::getMStreams());
    }
}
