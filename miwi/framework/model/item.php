<?php
/**
 * @package     Moomla.Legacy
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('MPATH_PLATFORM') or die;

abstract class MModelItem extends MModelLegacy
{

	protected $_item = null;
	protected $_context = 'group.type';

	protected function getStoreId($id = '')
	{
		// Compile the store id.
		return md5($id);
	}
}