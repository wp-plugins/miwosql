<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MInstaller {

	public static function runSqlFile($sql_file) {
		$db = MFactory::getDbo();

		if (!file_exists($sql_file)) {
			return;
		}

		$buffer = file_get_contents($sql_file);

		if ($buffer === false) {
			return;
		}

		$queries = $db->splitSql($buffer);

		if (count($queries) == 0) {
			return;
		}

		foreach ($queries as $query) {
			$query = trim($query);

			if ($query != '' && $query{0} != '#') {
				$db->setQuery($query);

				if (!$db->query()) {
					MError::raiseWarning(1, 'MInstaller::install: '.MText::_('SQL Error')." ".$db->stderr(true));
					return;
				}
			}
		}
	}
}