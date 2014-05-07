<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheControllerOutput extends MCacheController {

	protected $_id;
	protected $_group;
	protected $_locktest = null;

	public function start($id, $group = null) {
		$data = $this->cache->get($id, $group);

		$this->_locktest = new stdClass;
		$this->_locktest->locked = null;
		$this->_locktest->locklooped = null;

		if ($data === false) {
			$this->_locktest = $this->cache->lock($id, $group);
			if ($this->_locktest->locked == true && $this->_locktest->locklooped == true) {
				$data = $this->cache->get($id, $group);
			}
		}

		if ($data !== false) {
			$data = unserialize(trim($data));
			echo $data;
			if ($this->_locktest->locked == true) {
				$this->cache->unlock($id, $group);
			}
			return true;
		}
		else {
			if ($this->_locktest->locked == false) {
				$this->_locktest = $this->cache->lock($id, $group);
			}
			ob_start();
			ob_implicit_flush(false);

			$this->_id = $id;
			$this->_group = $group;

			return false;
		}
	}

	public function end() {
		$data = ob_get_contents();
		ob_end_clean();
		echo $data;

		$id = $this->_id;
		$group = $this->_group;
		$this->_id = null;
		$this->_group = null;

		$ret = $this->cache->store(serialize($data), $id, $group);

		if ($this->_locktest->locked == true) {
			$this->cache->unlock($id, $group);
		}

		return $ret;
	}
}
