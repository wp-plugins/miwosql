<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheControllerPage extends MCacheController {

	protected $_id;
	protected $_group;
	protected $_locktest = null;

	public function get($id = false, $group = 'page', $wrkarounds = true) {
		$data = false;

		if ($id == false) {
			$id = $this->_makeId();
		}

		if (!headers_sent() && isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			$etag = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']);
			if ($etag == $id) {
				$browserCache = isset($this->options['browsercache']) ? $this->options['browsercache'] : false;
				if ($browserCache) {
					$this->_noChange();
				}
			}
		}

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
			if ($wrkarounds === true) {
				$data = MCache::getWorkarounds($data);
			}

			$this->_setEtag($id);
			if ($this->_locktest->locked == true) {
				$this->cache->unlock($id, $group);
			}
			return $data;
		}

		$this->_id = $id;
		$this->_group = $group;
		return false;
	}

	public function store($wrkarounds = true) {
		$data = MResponse::getBody();

		$id = $this->_id;
		$group = $this->_group;
		$this->_id = null;
		$this->_group = null;

		if ($data) {
			$data = $wrkarounds == false ? $data : MCache::setWorkarounds($data);

			if ($this->_locktest->locked == false) {
				$this->_locktest = $this->cache->lock($id, $group);
			}

			$sucess = $this->cache->store(serialize($data), $id, $group);

			if ($this->_locktest->locked == true) {
				$this->cache->unlock($id, $group);
			}

			return $sucess;
		}
		return false;
	}

	protected function _makeId() {
		return MCache::makeId();
	}

	protected function _noChange() {
		$app = MFactory::getApplication();

		header('HTTP/1.x 304 Not Modified', true);
		$app->close();
	}

	protected function _setEtag($etag) {
		MResponse::setHeader('ETag', $etag, true);
	}
}
