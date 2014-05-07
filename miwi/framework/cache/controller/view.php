<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheControllerView extends MCacheController {

	public function get(&$view, $method, $id = false, $wrkarounds = true) {
		if ($id == false) {
			$id = $this->_makeId($view, $method);
		}

		$data = false;
		$data = $this->cache->get($id);

		$locktest = new stdClass;
		$locktest->locked = null;
		$locktest->locklooped = null;

		if ($data === false) {
			$locktest = $this->cache->lock($id, null);
			if ($locktest->locked == true && $locktest->locklooped == true) {
				$data = $this->cache->get($id);
			}
		}

		if ($data !== false) {
			$data = unserialize(trim($data));

			if ($wrkarounds === true) {
				echo MCache::getWorkarounds($data);
			}
			else {
				echo (isset($data)) ? $data : null;
			}

			if ($locktest->locked == true) {
				$this->cache->unlock($id);
			}

			return true;
		}

		if (method_exists($view, $method)) {
			if ($locktest->locked == false) {
				$locktest = $this->cache->lock($id);
			}

			ob_start();
			ob_implicit_flush(false);
			$view->$method();
			$data = ob_get_contents();
			ob_end_clean();
			echo $data;

			$cached = array();

			$cached = $wrkarounds == true ? MCache::setWorkarounds($data) : $data;

			$this->cache->store(serialize($cached), $id);

			if ($locktest->locked == true) {
				$this->cache->unlock($id);
			}
		}
		return false;
	}

	protected function _makeId(&$view, $method) {
		return md5(serialize(array(MCache::makeId(), get_class($view), $method)));
	}
}
