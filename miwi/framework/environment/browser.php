<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MBrowser extends MObject {

	protected $_majorVersion = 0;
	protected $_minorVersion = 0;
	protected $_browser = '';
	protected $_agent = '';
	protected $_lowerAgent = '';
	protected $_accept = '';
	protected $_accept_parsed = array();
	protected $_platform = '';
	protected $_robots = array(
		/* The most common ones. */
		'Googlebot',
		'msnbot',
		'Slurp',
		'Yahoo',
		/* The rest alphabetically. */
		'Arachnoidea',
		'ArchitextSpider',
		'Ask Jeeves',
		'B-l-i-t-z-Bot',
		'Baiduspider',
		'BecomeBot',
		'cfetch',
		'ConveraCrawler',
		'ExtractorPro',
		'FAST-WebCrawler',
		'FDSE robot',
		'fido',
		'geckobot',
		'Gigabot',
		'Girafabot',
		'grub-client',
		'Gulliver',
		'HTTrack',
		'ia_archiver',
		'InfoSeek',
		'kinjabot',
		'KIT-Fireball',
		'larbin',
		'LEIA',
		'lmspider',
		'Lycos_Spider',
		'Mediapartners-Google',
		'MuscatFerret',
		'NaverBot',
		'OmniExplorer_Bot',
		'polybot',
		'Pompos',
		'Scooter',
		'Teoma',
		'TheSuBot',
		'TurnitinBot',
		'Ultraseek',
		'ViolaBot',
		'webbandit',
		'www.almaden.ibm.com/cs/crawler',
		'ZyBorg');
		
	protected $_mobile = false;
	protected $_features = array(
		'html' => true,
		'wml' => false,
		'images' => true,
		'iframes' => false,
		'frames' => true,
		'tables' => true,
		'java' => true,
		'javascript' => true,
		'dom' => false,
		'utf' => false,
		'rte' => false,
		'homepage' => false,
		'accesskey' => false,
		'xmlhttpreq' => false,
		'xhtml+xml' => false,
		'mathml' => false,
		'svg' => false
	);

	protected $_quirks = array(
		'avoid_popup_windows' => false,
		'break_disposition_header' => false,
		'break_disposition_filename' => false,
		'broken_multipart_form' => false,
		'cache_same_url' => false,
		'cache_ssl_downloads' => false,
		'double_linebreak_textarea' => false,
		'empty_file_input_value' => false,
		'must_cache_forms' => false,
		'no_filename_spaces' => false,
		'no_hidden_overflow_tables' => false,
		'ow_gui_1.3' => false,
		'png_transparency' => false,
		'scrollbar_in_way' => false,
		'scroll_tds' => false,
		'windowed_controls' => false);

	protected $_images = array('jpeg', 'gif', 'png', 'pjpeg', 'x-png', 'bmp');
	protected static $instances = array();

	public function __construct($userAgent = null, $accept = null) {
		$this->match($userAgent, $accept);
	}

	static public function getInstance($userAgent = null, $accept = null) {
		$signature = serialize(array($userAgent, $accept));

		if (empty(self::$instances[$signature])) {
			self::$instances[$signature] = new MBrowser($userAgent, $accept);
		}

		return self::$instances[$signature];
	}

	public static function _sortMime($a, $b) {
		if ($a[1] > $b[1]) {
			return -1;
		}
		elseif ($a[1] < $b[1]) {
			return 1;
		}
		else {
			return 0;
		}
	}

	public function match($userAgent = null, $accept = null) {
		// Set our agent string.
		if (is_null($userAgent)) {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				$this->_agent = trim($_SERVER['HTTP_USER_AGENT']);
			}
		}
		else {
			$this->_agent = $userAgent;
		}
		$this->_lowerAgent = strtolower($this->_agent);

		// Set our accept string.
		if (is_null($accept)) {
			if (isset($_SERVER['HTTP_ACCEPT'])) {
				$this->_accept = strtolower(trim($_SERVER['HTTP_ACCEPT']));
			}
		}
		else {
			$this->_accept = strtolower($accept);
		}

		// Parse the HTTP Accept Header
		$accept_mime = explode(",", $this->_accept);
		for ($i = 0, $count = count($accept_mime); $i < $count; $i++) {
			$parts = explode(';q=', trim($accept_mime[$i]));
			if (count($parts) === 1) {
				$parts[1] = 1;
			}
			$accept_mime[$i] = $parts;
		}

		// Sort so the preferred value is the first
		usort($accept_mime, array(__CLASS__, '_sortMime'));

		$this->_accept_parsed = $accept_mime;

		// Check if browser accepts content type application/xhtml+xml. */* doesn't count ;)
		foreach ($this->_accept_parsed as $mime) {
			if (($mime[0] == 'application/xhtml+xml')) {
				$this->_setFeature('xhtml+xml');
			}
		}

		// Check for a mathplayer plugin is installed, so we can use MathML on several browsers.
		if (strpos($this->_lowerAgent, 'mathplayer') !== false) {
			$this->_setFeature('mathml');
		}

		// Check for UTF support.
		if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
			$this->_setFeature('utf', strpos(strtolower($_SERVER['HTTP_ACCEPT_CHARSET']), 'utf') !== false);
		}

		if (!empty($this->_agent)) {
			$this->_setPlatform();

			if (strpos($this->_lowerAgent, 'mobileexplorer') !== false
				|| strpos($this->_lowerAgent, 'openwave') !== false
				|| strpos($this->_lowerAgent, 'opera mini') !== false
				|| strpos($this->_lowerAgent, 'opera mobi') !== false
				|| strpos($this->_lowerAgent, 'operamini') !== false) {
				$this->_setFeature('frames', false);
				$this->_setFeature('javascript', false);
				$this->_setQuirk('avoid_popup_windows');
				$this->_mobile = true;
			}
			elseif (preg_match('|Opera[/ ]([0-9.]+)|', $this->_agent, $version)) {
				$this->setBrowser('opera');
				list ($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
				$this->_setFeature('javascript', true);
				$this->_setQuirk('no_filename_spaces');

				if ($this->_majorVersion >= 7) {
					$this->_setFeature('dom');
					$this->_setFeature('iframes');
					$this->_setFeature('accesskey');
					$this->_setQuirk('double_linebreak_textarea');
				}
				/* Due to changes in Opera UA, we need to check Version/xx.yy,
				 * but only if version is > 9.80. See: http://dev.opera.com/articles/view/opera-ua-string-changes/ */
				if ($this->_majorVersion == 9 && $this->_minorVersion >= 80) {
					$this->identifyBrowserVersion();
				}
			}
			elseif (preg_match('|Chrome[/ ]([0-9.]+)|', $this->_agent, $version)) {
				$this->setBrowser('chrome');
				list ($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
				$this->_setFeature('javascript', true);
			}
			elseif (preg_match('|CrMo[/ ]([0-9.]+)|', $this->_agent, $version)) {
				$this->setBrowser('chrome');
				list ($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
			}
			elseif (preg_match('|CriOS[/ ]([0-9.]+)|', $this->_agent, $version)) {
				$this->setBrowser('chrome');
				list ($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
				$this->_mobile = true;
			}
			elseif (strpos($this->_lowerAgent, 'elaine/') !== false
				|| strpos($this->_lowerAgent, 'palmsource') !== false
				|| strpos($this->_lowerAgent, 'digital paths') !== false) {
				$this->setBrowser('palm');
				$this->_setFeature('images', false);
				$this->_setFeature('frames', false);
				$this->_setFeature('javascript', false);
				$this->_setQuirk('avoid_popup_windows');
				$this->_mobile = true;
			}
			elseif ((preg_match('|MSIE ([0-9.]+)|', $this->_agent, $version)) || (preg_match('|Internet Explorer/([0-9.]+)|', $this->_agent, $version))) {
				$this->setBrowser('msie');
				$this->_setQuirk('cache_ssl_downloads');
				$this->_setQuirk('cache_same_url');
				$this->_setQuirk('break_disposition_filename');

				if (strpos($version[1], '.') !== false) {
					list ($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
				}
				else {
					$this->_majorVersion = $version[1];
					$this->_minorVersion = 0;
				}

				/* IE (< 7) on Windows does not support alpha transparency in
			 		 * PNG images. */
				if (($this->_majorVersion < 7) && preg_match('/windows/i', $this->_agent)) {
					$this->_setQuirk('png_transparency');
				}

				if (preg_match('/; (120x160|240x280|240x320|320x320)\)/', $this->_agent)) {
					$this->_mobile = true;
				}

				switch ($this->_majorVersion) {
					case 7:
						$this->_setFeature('javascript', 1.4);
						$this->_setFeature('dom');
						$this->_setFeature('iframes');
						$this->_setFeature('utf');
						$this->_setFeature('rte');
						$this->_setFeature('homepage');
						$this->_setFeature('accesskey');
						$this->_setFeature('xmlhttpreq');
						$this->_setQuirk('scrollbar_in_way');
						break;

					case 6:
						$this->_setFeature('javascript', 1.4);
						$this->_setFeature('dom');
						$this->_setFeature('iframes');
						$this->_setFeature('utf');
						$this->_setFeature('rte');
						$this->_setFeature('homepage');
						$this->_setFeature('accesskey');
						$this->_setFeature('xmlhttpreq');
						$this->_setQuirk('scrollbar_in_way');
						$this->_setQuirk('broken_multipart_form');
						$this->_setQuirk('windowed_controls');
						break;

					case 5:
						if ($this->getPlatform() == 'mac') {
							$this->_setFeature('javascript', 1.2);
						}
						else {
							// MSIE 5 for Windows.
							$this->_setFeature('javascript', 1.4);
							$this->_setFeature('dom');
							$this->_setFeature('xmlhttpreq');
							if ($this->_minorVersion >= 5) {
								$this->_setFeature('rte');
								$this->_setQuirk('windowed_controls');
							}
						}
						$this->_setFeature('iframes');
						$this->_setFeature('utf');
						$this->_setFeature('homepage');
						$this->_setFeature('accesskey');
						if ($this->_minorVersion == 5) {
							$this->_setQuirk('break_disposition_header');
							$this->_setQuirk('broken_multipart_form');
						}
						break;

					case 4:
						$this->_setFeature('javascript', 1.2);
						$this->_setFeature('accesskey');
						if ($this->_minorVersion > 0) {
							$this->_setFeature('utf');
						}
						break;

					case 3:
						$this->_setFeature('javascript', 1.5);
						$this->_setQuirk('avoid_popup_windows');
						break;
				}
			}
			elseif (preg_match('|amaya/([0-9.]+)|', $this->_agent, $version)) {
				$this->setBrowser('amaya');
				$this->_majorVersion = $version[1];
				if (isset($version[2])) {
					$this->_minorVersion = $version[2];
				}
				if ($this->_majorVersion > 1) {
					$this->_setFeature('mathml');
					$this->_setFeature('svg');
				}
				$this->_setFeature('xhtml+xml');
			}
			elseif (preg_match('|W3C_Validator/([0-9.]+)|', $this->_agent, $version)) {
				$this->_setFeature('mathml');
				$this->_setFeature('svg');
				$this->_setFeature('xhtml+xml');
			}
			elseif (preg_match('|ANTFresco/([0-9]+)|', $this->_agent, $version)) {
				$this->setBrowser('fresco');
				$this->_setFeature('javascript', 1.5);
				$this->_setQuirk('avoid_popup_windows');
			}
			elseif (strpos($this->_lowerAgent, 'avantgo') !== false) {
				$this->setBrowser('avantgo');
				$this->_mobile = true;
			}
			elseif (preg_match('|Konqueror/([0-9]+)|', $this->_agent, $version) || preg_match('|Safari/([0-9]+)\.?([0-9]+)?|', $this->_agent, $version)) {
				// Konqueror and Apple's Safari both use the KHTML
				// rendering engine.
				$this->setBrowser('konqueror');
				$this->_setQuirk('empty_file_input_value');
				$this->_setQuirk('no_hidden_overflow_tables');
				$this->_majorVersion = $version[1];
				if (isset($version[2])) {
					$this->_minorVersion = $version[2];
				}

				if (strpos($this->_agent, 'Safari') !== false && $this->_majorVersion >= 60) {
					// Safari.
					$this->setBrowser('safari');
					$this->_setFeature('utf');
					$this->_setFeature('javascript', 1.4);
					$this->_setFeature('dom');
					$this->_setFeature('iframes');
					if ($this->_majorVersion > 125 || ($this->_majorVersion == 125 && $this->_minorVersion >= 1)) {
						$this->_setFeature('accesskey');
						$this->_setFeature('xmlhttpreq');
					}
					if ($this->_majorVersion > 522) {
						$this->_setFeature('svg');
						$this->_setFeature('xhtml+xml');
					}
					// Set browser version, not engine version
					$this->identifyBrowserVersion();
				}
				else {
					// Konqueror.
					$this->_setFeature('javascript', 1.5);
					switch ($this->_majorVersion) {
						case 3:
							$this->_setFeature('dom');
							$this->_setFeature('iframes');
							$this->_setFeature('xhtml+xml');
							break;
					}
				}
			}
			elseif (preg_match('|Mozilla/([0-9.]+)|', $this->_agent, $version)) {
				$this->setBrowser('mozilla');
				$this->_setQuirk('must_cache_forms');

				list ($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
				switch ($this->_majorVersion) {
					case 5:
						if ($this->getPlatform() == 'win') {
							$this->_setQuirk('break_disposition_filename');
						}
						$this->_setFeature('javascript', 1.4);
						$this->_setFeature('dom');
						$this->_setFeature('accesskey');
						$this->_setFeature('xmlhttpreq');
						if (preg_match('|rv:(.*)\)|', $this->_agent, $revision)) {
							if ($revision[1] >= 1) {
								$this->_setFeature('iframes');
							}
							if ($revision[1] >= 1.3) {
								$this->_setFeature('rte');
							}
							if ($revision[1] >= 1.5) {
								$this->_setFeature('svg');
								$this->_setFeature('mathml');
								$this->_setFeature('xhtml+xml');
							}
						}
						break;

					case 4:
						$this->_setFeature('javascript', 1.3);
						$this->_setQuirk('buggy_compression');
						break;

					case 3:
					default:
						$this->_setFeature('javascript', 1);
						$this->_setQuirk('buggy_compression');
						break;
				}
			}
			elseif (preg_match('|Lynx/([0-9]+)|', $this->_agent, $version)) {
				$this->setBrowser('lynx');
				$this->_setFeature('images', false);
				$this->_setFeature('frames', false);
				$this->_setFeature('javascript', false);
				$this->_setQuirk('avoid_popup_windows');
			}
			elseif (preg_match('|Links \(([0-9]+)|', $this->_agent, $version)) {
				$this->setBrowser('links');
				$this->_setFeature('images', false);
				$this->_setFeature('frames', false);
				$this->_setFeature('javascript', false);
				$this->_setQuirk('avoid_popup_windows');
			}
			elseif (preg_match('|HotJava/([0-9]+)|', $this->_agent, $version)) {
				$this->setBrowser('hotjava');
				$this->_setFeature('javascript', false);
			}
			elseif (strpos($this->_agent, 'UP/') !== false || strpos($this->_agent, 'UP.B') !== false || strpos($this->_agent, 'UP.L') !== false) {
				$this->setBrowser('up');
				$this->_setFeature('html', false);
				$this->_setFeature('javascript', false);
				$this->_setFeature('wml');

				if (strpos($this->_agent, 'GUI') !== false && strpos($this->_agent, 'UP.Link') !== false) {

					$this->_setQuirk('ow_gui_1.3');
				}
				$this->_mobile = true;
			}
			elseif (strpos($this->_agent, 'Xiino/') !== false) {
				$this->setBrowser('xiino');
				$this->_setFeature('wml');
				$this->_mobile = true;
			}
			elseif (strpos($this->_agent, 'Palmscape/') !== false) {
				$this->setBrowser('palmscape');
				$this->_setFeature('javascript', false);
				$this->_setFeature('wml');
				$this->_mobile = true;
			}
			elseif (strpos($this->_agent, 'Nokia') !== false) {
				$this->setBrowser('nokia');
				$this->_setFeature('html', false);
				$this->_setFeature('wml');
				$this->_setFeature('xhtml');
				$this->_mobile = true;
			}
			elseif (strpos($this->_agent, 'Ericsson') !== false) {
				$this->setBrowser('ericsson');
				$this->_setFeature('html', false);
				$this->_setFeature('wml');
				$this->_mobile = true;
			}
			elseif (strpos($this->_lowerAgent, 'wap') !== false) {
				$this->setBrowser('wap');
				$this->_setFeature('html', false);
				$this->_setFeature('javascript', false);
				$this->_setFeature('wml');
				$this->_mobile = true;
			}
			elseif (strpos($this->_lowerAgent, 'docomo') !== false || strpos($this->_lowerAgent, 'portalmmm') !== false) {
				$this->setBrowser('imode');
				$this->_setFeature('images', false);
				$this->_mobile = true;
			}
			elseif (strpos($this->_agent, 'BlackBerry') !== false) {
				$this->setBrowser('blackberry');
				$this->_setFeature('html', false);
				$this->_setFeature('javascript', false);
				$this->_setFeature('wml');
				$this->_mobile = true;
			}
			elseif (strpos($this->_agent, 'MOT-') !== false) {
				$this->setBrowser('motorola');
				$this->_setFeature('html', false);
				$this->_setFeature('javascript', false);
				$this->_setFeature('wml');
				$this->_mobile = true;
			}
			elseif (strpos($this->_lowerAgent, 'j-') !== false) {
				$this->setBrowser('mml');
				$this->_mobile = true;
			}
		}
	}

	protected function _setPlatform() {
		if (strpos($this->_lowerAgent, 'wind') !== false) {
			$this->_platform = 'win';
		}
		elseif (strpos($this->_lowerAgent, 'mac') !== false) {
			$this->_platform = 'mac';
		}
		else {
			$this->_platform = 'unix';
		}
	}

	public function getPlatform() {
		return $this->_platform;
	}

	protected function identifyBrowserVersion() {
		if (preg_match('|Version[/ ]([0-9.]+)|', $this->_agent, $version)) {
			list ($this->_majorVersion, $this->_minorVersion) = explode('.', $version[1]);
			return;
		}
		// Can't identify browser version
		$this->_majorVersion = 0;
		$this->_minorVersion = 0;
		MLog::add("Can't identify browser version. Agent: " . $this->_agent, MLog::NOTICE);
	}

	public function setBrowser($browser) {
		$this->_browser = $browser;
	}

	public function getBrowser() {
		return $this->_browser;
	}

	public function getMajor() {
		return $this->_majorVersion;
	}

	public function getMinor() {
		return $this->_minorVersion;
	}

	public function getVersion() {
		return $this->_majorVersion . '.' . $this->_minorVersion;
	}

	public function getAgentString() {
		return $this->_agent;
	}

	public function getHTTPProtocol() {
		if (isset($_SERVER['SERVER_PROTOCOL'])) {
			if (($pos = strrpos($_SERVER['SERVER_PROTOCOL'], '/'))) {
				return substr($_SERVER['SERVER_PROTOCOL'], $pos + 1);
			}
		}
		return null;
	}

	private function _setQuirk($quirk, $value = true) {
		$this->_quirks[$quirk] = $value;
	}

	public function setQuirk($quirk, $value = true) {
		MLog::add('MBrowser::setQuirk() is deprecated.', MLog::WARNING, 'deprecated');
		$this->_quirks[$quirk] = $value;
	}

	public function hasQuirk($quirk) {
		MLog::add('MBrowser::hasQuirk() is deprecated.', MLog::WARNING, 'deprecated');
		return !empty($this->_quirks[$quirk]);
	}

	public function getQuirk($quirk) {
		MLog::add('MBrowser::getQuirk() is deprecated.', MLog::WARNING, 'deprecated');
		return isset($this->_quirks[$quirk]) ? $this->_quirks[$quirk] : null;
	}

	private function _setFeature($feature, $value = true) {
		$this->_features[$feature] = $value;
	}

	public function setFeature($feature, $value = true) {
		MLog::add('MBrowser::setFeature() is deprecated.', MLog::WARNING, 'deprecated');
		$this->_features[$feature] = $value;
	}

	public function hasFeature($feature) {
		MLog::add('MBrowser::hasFeature() is deprecated.', MLog::WARNING, 'deprecated');
		return !empty($this->_features[$feature]);
	}

	public function getFeature($feature) {
		MLog::add('MBrowser::getFeature() is deprecated.', MLog::WARNING, 'deprecated');
		return isset($this->_features[$feature]) ? $this->_features[$feature] : null;
	}

	public function isViewable($mimetype) {
		$mimetype = strtolower($mimetype);
		list ($type, $subtype) = explode('/', $mimetype);

		if (!empty($this->_accept)) {
			$wildcard_match = false;

			if (strpos($this->_accept, $mimetype) !== false) {
				return true;
			}

			if (strpos($this->_accept, '*/*') !== false) {
				$wildcard_match = true;
				if ($type != 'image') {
					return true;
				}
			}

			// Deal with Mozilla pjpeg/jpeg issue
			if ($this->isBrowser('mozilla') && ($mimetype == 'image/pjpeg') && (strpos($this->_accept, 'image/jpeg') !== false)) {
				return true;
			}

			if (!$wildcard_match) {
				return false;
			}
		}

		if (!$this->hasFeature('images') || ($type != 'image')) {
			return false;
		}

		return (in_array($subtype, $this->_images));
	}

	public function isBrowser($browser) {
		return ($this->_browser === $browser);
	}

	public function isRobot() {
		foreach ($this->_robots as $robot) {
			if (strpos($this->_agent, $robot) !== false) {
				return true;
			}
		}
		return false;
	}

	public function isMobile() {
		return $this->_mobile;
	}

	public function isSSLConnection() {
		return ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) || getenv('SSL_PROTOCOL_VERSION'));
	}
}