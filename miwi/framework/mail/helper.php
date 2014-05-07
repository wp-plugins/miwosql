<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
*/

defined('MIWI') or die('MIWI');

abstract class MMailHelper {

	public static function cleanLine($value) {
		return trim(preg_replace('/(%0A|%0D|\n+|\r+)/i', '', $value));
	}

	public static function cleanText($value) {
		return trim(preg_replace('/(%0A|%0D|\n+|\r+)(content-type:|to:|cc:|bcc:)/i', '', $value));
	}

	public static function cleanBody($body) {
		// Strip all email headers from a string
		return preg_replace("/((From:|To:|Cc:|Bcc:|Subject:|Content-type:) ([\S]+))/", "", $body);
	}

	public static function cleanSubject($subject) {
		return preg_replace("/((From:|To:|Cc:|Bcc:|Content-type:) ([\S]+))/", "", $subject);
	}

	public static function cleanAddress($address) {
		if (preg_match("[\s;,]", $address)) {
			return false;
		}
		
		return $address;
	}

	public static function isEmailAddress($email) {
		// Split the email into a local and domain
		$atIndex = strrpos($email, "@");
		$domain = substr($email, $atIndex + 1);
		$local = substr($email, 0, $atIndex);

		// Check Length of domain
		$domainLen = strlen($domain);
		if ($domainLen < 1 || $domainLen > 255) {
			return false;
		}

		$allowed = 'A-Za-z0-9!#&*+=?_-';
		$regex = "/^[$allowed][\.$allowed]{0,63}$/";
		if (!preg_match($regex, $local) || substr($local, -1) == '.') {
			return false;
		}

		// No problem if the domain looks like an IP address, ish
		$regex = '/^[0-9\.]+$/';
		if (preg_match($regex, $domain)) {
			return true;
		}

		// Check Lengths
		$localLen = strlen($local);
		if ($localLen < 1 || $localLen > 64) {
			return false;
		}

		// Check the domain
		$domain_array = explode(".", rtrim($domain, '.'));
		$regex = '/^[A-Za-z0-9-]{0,63}$/';
		foreach ($domain_array as $domain) {
			// Must be something
			if (!$domain) {
				return false;
			}

			// Check for invalid characters
			if (!preg_match($regex, $domain)) {
				return false;
			}

			// Check for a dash at the beginning of the domain
			if (strpos($domain, '-') === 0) {
				return false;
			}

			// Check for a dash at the end of the domain
			$length = strlen($domain) - 1;
			if (strpos($domain, '-', $length) === $length) {
				return false;
			}
		}

		return true;
	}
}