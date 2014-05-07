<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MCacheControllerCallback extends MCacheController {

	public function call() {
		$args = func_get_args();
		$callback = array_shift($args);

		return $this->get($callback, $args);
	}

	public function get($callback, $args = array(), $id = false, $wrkarounds = false, $woptions = array()) {

		if (is_array($callback)) {
			// We have a standard php callback array -- do nothing
		}
		elseif (strstr($callback, '::')) {
			list ($class, $method) = explode('::', $callback);
			$callback = array(trim($class), trim($method));
		}
		elseif (strstr($callback, '->')) {
			list ($object_123456789, $method) = explode('->', $callback);
			global $$object_123456789;
			$callback = array($$object_123456789, $method);
		}
		else {
			// We have just a standard function -- do nothing
		}

		if (!$id) {
			$id = $this->_makeId($callback, $args);
		}

		$data = false;
		$data = $this->cache->get($id);

		$locktest = new stdClass;
		$locktest->locked = null;
		$locktest->locklooped = null;

		if ($data === false) {
			$locktest = $this->cache->lock($id);
			if ($locktest->locked == true && $locktest->locklooped == true) {
				$data = $this->cache->get($id);
			}
		}

		$coptions = array();

		if ($data !== false) {
			$cached = unserialize(trim($data));
			$coptions['mergehead'] = isset($woptions['mergehead']) ? $woptions['mergehead'] : 0;
			$output = ($wrkarounds == false) ? $cached['output'] : MCache::getWorkarounds($cached['output'], $coptions);
			$result = $cached['result'];
			
			if ($locktest->locked == true) {
				$this->cache->unlock($id);
			}

		}
		else {

			if (!is_array($args)) {
				$Args = !empty($args) ? array(&$args) : array();
			}
			else {
				$Args = &$args;
			}

			if ($locktest->locked == false) {
				$locktest = $this->cache->lock($id);
			}

			if (isset($woptions['modulemode'])) {
				$document = MFactory::getDocument();
				$coptions['modulemode'] = $woptions['modulemode'];
				$coptions['headerbefore'] = $document->getHeadData();
			}
			else {
				$coptions['modulemode'] = 0;
			}

			ob_start();
			ob_implicit_flush(false);

			$result = call_user_func_array($callback, $Args);
			$output = ob_get_contents();

			ob_end_clean();

			$cached = array();

			$coptions['nopathway'] = isset($woptions['nopathway']) ? $woptions['nopathway'] : 1;
			$coptions['nohead'] = isset($woptions['nohead']) ? $woptions['nohead'] : 1;
			$coptions['nomodules'] = isset($woptions['nomodules']) ? $woptions['nomodules'] : 1;

			$cached['output'] = ($wrkarounds == false) ? $output : MCache::setWorkarounds($output, $coptions);
			$cached['result'] = $result;

			$this->cache->store(serialize($cached), $id);
			if ($locktest->locked == true) {
				$this->cache->unlock($id);
			}
		}

		echo $output;
		return $result;
	}

	protected function _makeId($callback, $args) {
		if (is_array($callback) && is_object($callback[0])) {
			$vars = get_object_vars($callback[0]);
			$vars[] = strtolower(get_class($callback[0]));
			$callback[0] = $vars;
		}

		return md5(serialize(array($callback, $args)));
	}
}
