<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDate extends DateTime {

	const DAY_ABBR = "\x021\x03";
	const DAY_NAME = "\x022\x03";
	const MONTH_ABBR = "\x023\x03";
	const MONTH_NAME = "\x024\x03";

	public static $format = 'Y-m-d H:i:s';
	protected static $gmt;
	protected static $stz;
	protected static $offsets = array('-12' => 'Etc/GMT-12', '-11' => 'Pacific/Midway', '-10' => 'Pacific/Honolulu', '-9.5' => 'Pacific/Marquesas',
		'-9' => 'US/Alaska', '-8' => 'US/Pacific', '-7' => 'US/Mountain', '-6' => 'US/Central', '-5' => 'US/Eastern', '-4.5' => 'America/Caracas',
		'-4' => 'America/Barbados', '-3.5' => 'Canada/Newfoundland', '-3' => 'America/Buenos_Aires', '-2' => 'Atlantic/South_Georgia',
		'-1' => 'Atlantic/Azores', '0' => 'Europe/London', '1' => 'Europe/Amsterdam', '2' => 'Europe/Istanbul', '3' => 'Asia/Riyadh',
		'3.5' => 'Asia/Tehran', '4' => 'Asia/Muscat', '4.5' => 'Asia/Kabul', '5' => 'Asia/Karachi', '5.5' => 'Asia/Calcutta',
		'5.75' => 'Asia/Katmandu', '6' => 'Asia/Dhaka', '6.5' => 'Indian/Cocos', '7' => 'Asia/Bangkok', '8' => 'Australia/Perth',
		'8.75' => 'Australia/West', '9' => 'Asia/Tokyo', '9.5' => 'Australia/Adelaide', '10' => 'Australia/Brisbane',
		'10.5' => 'Australia/Lord_Howe', '11' => 'Pacific/Kosrae', '11.5' => 'Pacific/Norfolk', '12' => 'Pacific/Auckland',
		'12.75' => 'Pacific/Chatham', '13' => 'Pacific/Tongatapu', '14' => 'Pacific/Kiritimati');

	protected $_tz;

	public function __construct($date = 'now', $tz = null) {
		
		if (empty(self::$gmt) || empty(self::$stz)) {
			self::$gmt = new DateTimeZone('GMT');
			self::$stz = new DateTimeZone(@date_default_timezone_get());
		}

		if (!($tz instanceof DateTimeZone)) {
			if ($tz === null) {
				$tz = self::$gmt;
			}
			elseif (is_numeric($tz)) {
				$tz = new DateTimeZone(self::$offsets[(string) $tz]);
			}
			elseif (is_string($tz)) {
				$tz = new DateTimeZone($tz);
			}
		}

		date_default_timezone_set('UTC');
		$date = is_numeric($date) ? date('c', $date) : $date;

		parent::__construct($date, $tz);

		date_default_timezone_set(self::$stz->getName());

		$this->_tz = $tz;
	}

	public function __get($name) {
		$value = null;

		switch ($name) {
			case 'daysinmonth':
				$value = $this->format('t', true);
				break;
			case 'dayofweek':
				$value = $this->format('N', true);
				break;
			case 'dayofyear':
				$value = $this->format('z', true);
				break;
			case 'isleapyear':
				$value = (boolean) $this->format('L', true);
				break;
			case 'day':
				$value = $this->format('d', true);
				break;
			case 'hour':
				$value = $this->format('H', true);
				break;
			case 'minute':
				$value = $this->format('i', true);
				break;
			case 'second':
				$value = $this->format('s', true);
				break;
			case 'month':
				$value = $this->format('m', true);
				break;
			case 'ordinal':
				$value = $this->format('S', true);
				break;
			case 'week':
				$value = $this->format('W', true);
				break;
			case 'year':
				$value = $this->format('Y', true);
				break;
			default:
				$trace = debug_backtrace();
				trigger_error(
					'Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'],
					E_USER_NOTICE
				);
		}

		return $value;
	}

	public function __toString() {
		return (string) parent::format(self::$format);
	}

	public static function getInstance($date = 'now', $tz = null) {
		return new MDate($date, $tz);
	}

	public function dayToString($day, $abbr = false) {
		switch ($day) {
			case 0:
				return $abbr ? MText::_('SUN') : MText::_('SUNDAY');
			case 1:
				return $abbr ? MText::_('MON') : MText::_('MONDAY');
			case 2:
				return $abbr ? MText::_('TUE') : MText::_('TUESDAY');
			case 3:
				return $abbr ? MText::_('WED') : MText::_('WEDNESDAY');
			case 4:
				return $abbr ? MText::_('THU') : MText::_('THURSDAY');
			case 5:
				return $abbr ? MText::_('FRI') : MText::_('FRIDAY');
			case 6:
				return $abbr ? MText::_('SAT') : MText::_('SATURDAY');
		}
	}

	public function calendar($format, $local = false, $translate = true) {
		return $this->format($format, $local, $translate);
	}

	public function format($format, $local = false, $translate = true) {
		if ($translate) {
			$format = preg_replace('/(^|[^\\\])D/', "\\1" . self::DAY_ABBR, $format);
			$format = preg_replace('/(^|[^\\\])l/', "\\1" . self::DAY_NAME, $format);
			$format = preg_replace('/(^|[^\\\])M/', "\\1" . self::MONTH_ABBR, $format);
			$format = preg_replace('/(^|[^\\\])F/', "\\1" . self::MONTH_NAME, $format);
		}

		if ($local == false) {
			parent::setTimezone(self::$gmt);
		}

		$return = parent::format($format);

		if ($translate) {
			if (strpos($return, self::DAY_ABBR) !== false) {
				$return = str_replace(self::DAY_ABBR, $this->dayToString(parent::format('w'), true), $return);
			}

			if (strpos($return, self::DAY_NAME) !== false) {
				$return = str_replace(self::DAY_NAME, $this->dayToString(parent::format('w')), $return);
			}

			if (strpos($return, self::MONTH_ABBR) !== false) {
				$return = str_replace(self::MONTH_ABBR, $this->monthToString(parent::format('n'), true), $return);
			}

			if (strpos($return, self::MONTH_NAME) !== false) {
				$return = str_replace(self::MONTH_NAME, $this->monthToString(parent::format('n')), $return);
			}
		}

		if ($local == false) {
			parent::setTimezone($this->_tz);
		}

		return $return;
	}

	public function getOffsetFromGMT($hours = false) {
		return (float) $hours ? ($this->_tz->getOffset($this) / 3600) : $this->_tz->getOffset($this);
	}

	public function monthToString($month, $abbr = false) {
		switch ($month) {
			case 1:
				return $abbr ? MText::_('JANUARY_SHORT') : MText::_('JANUARY');
			case 2:
				return $abbr ? MText::_('FEBRUARY_SHORT') : MText::_('FEBRUARY');
			case 3:
				return $abbr ? MText::_('MARCH_SHORT') : MText::_('MARCH');
			case 4:
				return $abbr ? MText::_('APRIL_SHORT') : MText::_('APRIL');
			case 5:
				return $abbr ? MText::_('MAY_SHORT') : MText::_('MAY');
			case 6:
				return $abbr ? MText::_('JUNE_SHORT') : MText::_('JUNE');
			case 7:
				return $abbr ? MText::_('JULY_SHORT') : MText::_('JULY');
			case 8:
				return $abbr ? MText::_('AUGUST_SHORT') : MText::_('AUGUST');
			case 9:
				return $abbr ? MText::_('SEPTEMBER_SHORT') : MText::_('SEPTEMBER');
			case 10:
				return $abbr ? MText::_('OCTOBER_SHORT') : MText::_('OCTOBER');
			case 11:
				return $abbr ? MText::_('NOVEMBER_SHORT') : MText::_('NOVEMBER');
			case 12:
				return $abbr ? MText::_('DECEMBER_SHORT') : MText::_('DECEMBER');
		}
	}

	public function setOffset($offset) {
		MLog::add('MDate::setOffset() is deprecated.', MLog::WARNING, 'deprecated');

		if (isset(self::$offsets[(string) $offset])) {
			$this->_tz = new DateTimeZone(self::$offsets[(string) $offset]);
			$this->setTimezone($this->_tz);
			return true;
		}

		return false;
	}

	public function setTimezone($tz) {
		$this->_tz = $tz;
		return parent::setTimezone($tz);
	}

	public function toFormat($format = '%Y-%m-%d %H:%M:%S', $local = false) {
		MLog::add('MDate::toFormat() is deprecated.', MLog::WARNING, 'deprecated');

		date_default_timezone_set('GMT');

		$time = (int) parent::format('U');

		if ($local) {
			$time += $this->getOffsetFromGMT();
		}

		if (strpos($format, '%a') !== false) {
			$format = str_replace('%a', $this->dayToString(date('w', $time), true), $format);
		}
		if (strpos($format, '%A') !== false) {
			$format = str_replace('%A', $this->dayToString(date('w', $time)), $format);
		}
		if (strpos($format, '%b') !== false) {
			$format = str_replace('%b', $this->monthToString(date('n', $time), true), $format);
		}
		if (strpos($format, '%B') !== false) {
			$format = str_replace('%B', $this->monthToString(date('n', $time)), $format);
		}

		$date = strftime($format, $time);

		date_default_timezone_set(self::$stz->getName());

		return $date;
	}

	public function toISO8601($local = false) {
		return $this->format(DateTime::RFC3339, $local, false);
	}

	public function toMySQL($local = false) {
		MLog::add('MDate::toMySQL() is deprecated. Use MDate::toSql() instead.', MLog::WARNING, 'deprecated');
		return $this->format('Y-m-d H:i:s', $local, false);
	}

	public function toSql($local = false, MDatabase $dbo = null) {
		if ($dbo === null) {
			$dbo = MFactory::getDbo();
		}
		return $this->format($dbo->getDateFormat(), $local, false);
	}

	public function toRFC822($local = false) {
		return $this->format(DateTime::RFC2822, $local, false);
	}

	public function toUnix() {
		return (int) parent::format('U');
	}
}