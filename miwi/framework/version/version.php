<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

final class MVersion {
	/** @var  string  Product name. */
	public $PRODUCT = 'Miwi';

	/** @var  string  Release version. */
	public $RELEASE = '1.0';

	/** @var  string  Maintenance version. */
	public $DEV_LEVEL = '0';

	/** @var  string  Development STATUS. */
	public $DEV_STATUS = 'Stable';

	/** @var  string  Build number. */
	public $BUILD = '';

	/** @var  string  Code name. */
	public $CODENAME = 'Start';

	/** @var  string  Release date. */
	public $RELDATE = '15-March-2014';

	/** @var  string  Release time. */
	public $RELTIME = '14:30';

	/** @var  string  Release timezone. */
	public $RELTZ = 'GMT';

	/** @var  string  Copyright Notice. */
	public $COPYRIGHT = 'Copyright (C) 2009 - 2014 Miwisoft LLC.';

	/** @var  string  Link text. */
	public $URL = '';

	public function isCompatible($minimum) {
		return version_compare(MVERSION, $minimum, 'ge');
	}

	public function getHelpVersion() {
		return '.' . str_replace('.', '', $this->RELEASE);
	}

	public function getShortVersion() {
		return $this->RELEASE . '.' . $this->DEV_LEVEL;
	}

	public function getLongVersion() {
		return $this->PRODUCT . ' ' . $this->RELEASE . '.' . $this->DEV_LEVEL . ' '
				. $this->DEV_STATUS . ' [ ' . $this->CODENAME . ' ] ' . $this->RELDATE . ' '
				. $this->RELTIME . ' ' . $this->RELTZ;
	}

	public function getUserAgent($component = null, $mask = false, $add_version = true) {
		if ($component === null) {
			$component = 'Framework';
		}

		if ($add_version) {
			$component .= '/' . $this->RELEASE;
		}

		// If masked pretend to look like Mozilla 5.0 but still identify ourselves.
		if ($mask) {
			return 'Mozilla/5.0 ' . $this->PRODUCT . '/' . $this->RELEASE . '.' . $this->DEV_LEVEL . ($component ? ' ' . $component : '');
		}
		else {
			return $this->PRODUCT . '/' . $this->RELEASE . '.' . $this->DEV_LEVEL . ($component ? ' ' . $component : '');
		}
	}
}