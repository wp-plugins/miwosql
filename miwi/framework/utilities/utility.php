<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU/GPL based on Moomla www.joomla.org
*/

defined('MIWI') or die('MIWI');

class MUtility {

	public static function sendMail($from, $fromname, $recipient, $subject, $body, $mode = 0, $cc = null, $bcc = null, $attachment = null,
		$replyto = null, $replytoname = null) {
		// Deprecation warning.
		MLog::add('MUtility::sendmail() is deprecated.', MLog::WARNING, 'deprecated');

		// Get a MMail instance
		$mail = MFactory::getMailer();

		return $mail->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
	}

	public static function sendAdminMail($adminName, $adminEmail, $email, $type, $title, $author, $url = null) {
		// Deprecation warning.
		MLog::add('MUtility::sendAdminMail() is deprecated.', MLog::WARNING, 'deprecated');

		// Get a MMail instance
		$mail = MFactory::getMailer();

		return $mail->sendAdminMail($adminName, $adminEmail, $email, $type, $title, $author, $url);
	}

	public static function getHash($seed) {
		// Deprecation warning.
		MLog::add('MUtility::getHash() is deprecated. Use MApplication::getHash() instead.', MLog::WARNING, 'deprecated');

		return MApplication::getHash($seed);
	}

	public static function getToken($forceNew = false) {
		// Deprecation warning.
		MLog::add('MUtility::getToken() is deprecated. Use MSession::getFormToken() instead.', MLog::WARNING, 'deprecated');

		$session = MFactory::getSession();
		return $session->getFormToken($forceNew);
	}

	public static function parseAttributes($string) {
		// Initialise variables.
		$attr = array();
		$retarray = array();

		// Let's grab all the key/value pairs using a regular expression
		preg_match_all('/([\w:-]+)[\s]?=[\s]?"([^"]*)"/i', $string, $attr);

		if (is_array($attr)) {
			$numPairs = count($attr[1]);
			for ($i = 0; $i < $numPairs; $i++) {
				$retarray[$attr[1][$i]] = $attr[2][$i];
			}
		}

		return $retarray;
	}

	public static function isWinOS() {
		// Deprecation warning.
		MLog::add('MUtility::isWinOS() is deprecated.', MLog::WARNING, 'deprecated');

		$application = MFactory::getApplication();

		return $application->isWinOS();
	}

	public static function dump(&$var, $htmlSafe = true) {
		// Deprecation warning.
		MLog::add('MUtility::dump() is deprecated.', MLog::WARNING, 'deprecated');

		$result = var_export($var, true);

		return '<pre>' . ($htmlSafe ? htmlspecialchars($result, ENT_COMPAT, 'UTF-8') : $result) . '</pre>';
	}

	public function array_unshift_ref(&$array, &$value) {
		// Deprecation warning.
		MLog::add('MUtility::array_unshift_ref() is deprecated.', MLog::WARNING, 'deprecated');

		$return = array_unshift($array, '');
		$array[0] = &$value;

		return $return;
	}

	public function return_bytes($val) {
		// Deprecation warning.
		MLog::add('MUtility::return_bytes() is deprecated.', MLog::WARNING, 'deprecated');

		$val = trim($val);
		$last = strtolower($val{strlen($val) - 1});

		switch ($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}
}