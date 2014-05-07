<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormRuleUrl extends MFormRule {

    public function test(&$element, $value, $group = null, &$input = null, &$form = null) {
        // If the field is empty and not required, the field is valid.
        $required = ((string)$element['required'] == 'true' || (string)$element['required'] == 'required');
        if (!$required && empty($value)) {
            return true;
        }
        $urlParts = MString::parse_url($value);
        // See http://www.w3.org/Addressing/URL/url-spec.txt
        // Use the full list or optionally specify a list of permitted schemes.
        if ($element['schemes'] == '') {
            $scheme = array('http', 'https', 'ftp', 'ftps', 'gopher', 'mailto', 'news', 'prospero', 'telnet', 'rlogin', 'tn3270', 'wais', 'url',
                'mid', 'cid', 'nntp', 'tel', 'urn', 'ldap', 'file', 'fax', 'modem', 'git');
        }
        else {
            $scheme = explode(',', $element['schemes']);

        }
        // This rule is only for full URLs with schemes because  parse_url does not parse
        // accurately without a scheme.
        // @see http://php.net/manual/en/function.parse-url.php
        if (!array_key_exists('scheme', $urlParts)) {
            return false;
        }
        $urlScheme = (string)$urlParts['scheme'];
        $urlScheme = strtolower($urlScheme);
        if (in_array($urlScheme, $scheme) == false) {
            return false;
        }
        // For some schemes here must be two slashes.
        if (($urlScheme == 'http' || $urlScheme == 'https' || $urlScheme == 'ftp' || $urlScheme == 'sftp' || $urlScheme == 'gopher'
                || $urlScheme == 'wais' || $urlScheme == 'gopher' || $urlScheme == 'prospero' || $urlScheme == 'telnet' || $urlScheme == 'git')
            && ((substr($value, strlen($urlScheme), 3)) !== '://')
        ) {
            return false;
        }
        // The best we can do for the rest is make sure that the strings are valid UTF-8
        // and the port is an integer.
        if (array_key_exists('host', $urlParts) && !MString::valid((string)$urlParts['host'])) {
            return false;
        }
        if (array_key_exists('port', $urlParts) && !is_int((int)$urlParts['port'])) {
            return false;
        }
        if (array_key_exists('path', $urlParts) && !MString::valid((string)$urlParts['path'])) {
            return false;
        }

        return true;
    }
}