<?php

/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

class MResponse {

    protected static $body = array();

    protected static $cachable = false;

    protected static $headers = array();

    public static function allowCache($allow = null) {
        if (!is_null($allow)) {
            self::$cachable = (bool)$allow;
        }

        return self::$cachable;
    }

    public static function setHeader($name, $value, $replace = false) {
        $name = (string)$name;
        $value = (string)$value;

        if ($replace) {
            foreach (self::$headers as $key => $header) {
                if ($name == $header['name']) {
                    unset(self::$headers[$key]);
                }
            }
        }

        self::$headers[] = array('name' => $name, 'value' => $value);
    }

    public static function getHeaders() {
        return self::$headers;
    }

    public static function clearHeaders() {
        self::$headers = array();
    }

    public static function sendHeaders() {
        if (!headers_sent()) {
            foreach (self::$headers as $header) {
                if ('status' == strtolower($header['name'])) {
                    // 'status' headers indicate an HTTP status, and need to be handled slightly differently
                    header(ucfirst(strtolower($header['name'])) . ': ' . $header['value'], null, (int)$header['value']);
                }
                else {
                    header($header['name'] . ': ' . $header['value'], false);
                }
            }
        }
    }

    public static function setBody($content) {
        self::$body = array((string)$content);
    }

    public static function prependBody($content) {
        array_unshift(self::$body, (string)$content);
    }

    public static function appendBody($content) {
        array_push(self::$body, (string)$content);
    }

    public static function getBody($toArray = false) {
        if ($toArray) {
            return self::$body;
        }

        ob_start();
        foreach (self::$body as $content) {
            echo $content;
        }

        return ob_get_clean();
    }

    public static function toString($compress = false) {
        $data = self::getBody();

        // Don't compress something if the server is going to do it anyway. Waste of time.
        if ($compress && !ini_get('zlib.output_compression') && ini_get('output_handler') != 'ob_gzhandler') {
            $data = self::compress($data);
        }

        if (self::allowCache() === false) {
            self::setHeader('Cache-Control', 'no-cache', false);
            // HTTP 1.0
            self::setHeader('Pragma', 'no-cache');
        }

        self::sendHeaders();

        return $data;
    }

    protected static function compress($data) {
        $encoding = self::clientEncoding();

        if (!$encoding) {
            return $data;
        }

        if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
            return $data;
        }

        if (headers_sent()) {
            return $data;
        }

        if (connection_status() !== 0) {
            return $data;
        }

        // Ideal level
        $level = 4;

        $gzdata = gzencode($data, $level);

        self::setHeader('Content-Encoding', $encoding);
        self::setHeader('X-Content-Encoded-By', 'Miwisoft');

        return $gzdata;
    }

    protected static function clientEncoding() {
        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            return false;
        }

        $encoding = false;

        if (false !== strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
            $encoding = 'gzip';
        }

        if (false !== strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip')) {
            $encoding = 'x-gzip';
        }

        return $encoding;
    }
}
