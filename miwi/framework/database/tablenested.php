<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MTableNested extends MTable {

	public $parent_id;
	public $level;
	public $lft;
	public $rgt;
	public $alias;
	protected $_location;
	protected $_location_id;
	protected $_cache = array ();
	protected $_debug = 0;

	public function debug($level) {
		$this->_debug = intval($level);
	}

	public function getPath($pk = null, $diagnostic = false) {
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;
		
		$query = $this->_db->getQuery(true);
		$select = ($diagnostic) ? 'p.' . $k . ', p.parent_id, p.level, p.lft, p.rgt' : 'p.*';
		$query->select($select);
		$query->from($this->_tbl . ' AS n, ' . $this->_tbl . ' AS p');
		$query->where('n.lft BETWEEN p.lft AND p.rgt');
		$query->where('n.' . $k . ' = ' . (int) $pk);
		$query->order('p.lft');
		
		$this->_db->setQuery($query);
		$path = $this->_db->loadObjectList();
		
		if ($this->_db->getErrorNum()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_GET_PATH_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			return false;
		}
		
		return $path;
	}

	public function getTree($pk = null, $diagnostic = false) {
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;
		
		$query = $this->_db->getQuery(true);
		$select = ($diagnostic) ? 'n.' . $k . ', n.parent_id, n.level, n.lft, n.rgt' : 'n.*';
		$query->select($select);
		$query->from($this->_tbl . ' AS n, ' . $this->_tbl . ' AS p');
		$query->where('n.lft BETWEEN p.lft AND p.rgt');
		$query->where('p.' . $k . ' = ' . (int) $pk);
		$query->order('n.lft');
		$this->_db->setQuery($query);
		$tree = $this->_db->loadObjectList();
		
		if ($this->_db->getErrorNum()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_GET_TREE_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			return false;
		}
		
		return $tree;
	}

	public function isLeaf($pk = null) {
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;
		
		if (! $node = $this->_getNode($pk)) {
			return false;
		}
		
		return (($node->rgt - $node->lft) == 1);
	}

	public function setLocation($referenceId, $position = 'after') {
		if (($position != 'before') && ($position != 'after') && ($position != 'first-child') && ($position != 'last-child')) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_INVALID_LOCATION', get_class($this)));
			$this->setError($e);
			return false;
		}
		
		$this->_location = $position;
		$this->_location_id = $referenceId;
		
		return true;
	}

	public function move($delta, $where = '') {
		$k = $this->_tbl_key;
		$pk = $this->$k;
		
		$query = $this->_db->getQuery(true);
		$query->select($k);
		$query->from($this->_tbl);
		$query->where('parent_id = ' . $this->parent_id);
		if ($where) {
			$query->where($where);
		}
		$position = 'after';
		if ($delta > 0) {
			$query->where('rgt > ' . $this->rgt);
			$query->order('rgt ASC');
			$position = 'after';
		}
		else {
			$query->where('lft < ' . $this->lft);
			$query->order('lft DESC');
			$position = 'before';
		}
		
		$this->_db->setQuery($query);
		$referenceId = $this->_db->loadResult();
		
		if ($referenceId) {
			return $this->moveByReference($referenceId, $position, $pk);
		}
		else {
			return false;
		}
	}

	public function moveByReference($referenceId, $position = 'after', $pk = null) {
		if ($this->_debug) {
			echo "\nMoving ReferenceId:$referenceId, Position:$position, PK:$pk";
		}
		
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;
		
		if (! $node = $this->_getNode($pk)) {
			return false;
		}
		
		$query = $this->_db->getQuery(true);
		$query->select($k);
		$query->from($this->_tbl);
		$query->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
		$this->_db->setQuery($query);
		$children = $this->_db->loadColumn();
		
		if ($this->_db->getErrorNum()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_MOVE_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			return false;
		}
		if ($this->_debug) {
			$this->_logtable(false);
		}
		
		if (in_array($referenceId, $children)) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_INVALID_NODE_RECURSION', get_class($this)));
			$this->setError($e);
			return false;
		}
		
		if (! $this->_lock()) {
			return false;
		}
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('lft = lft * (-1), rgt = rgt * (-1)');
		$query->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
		$this->_db->setQuery($query);
		
		$this->_runQuery($query, 'MLIB_DATABASE_ERROR_MOVE_FAILED');
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('lft = lft - ' . (int) $node->width);
		$query->where('lft > ' . (int) $node->rgt);
		$this->_db->setQuery($query);
		
		$this->_runQuery($query, 'MLIB_DATABASE_ERROR_MOVE_FAILED');
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('rgt = rgt - ' . (int) $node->width);
		$query->where('rgt > ' . (int) $node->rgt);
		$this->_db->setQuery($query);
		
		$this->_runQuery($query, 'MLIB_DATABASE_ERROR_MOVE_FAILED');
		
		if ($referenceId) {
			if (! $reference = $this->_getNode($referenceId)) {
				$this->_unlock();
				return false;
			}
			
			if (! $repositionData = $this->_getTreeRepositionData($reference, $node->width, $position)) {
				$this->_unlock();
				return false;
			}
		}
		else {
			$query = $this->_db->getQuery(true);
			$query->select($this->_tbl_key . ', parent_id, level, lft, rgt');
			$query->from($this->_tbl);
			$query->where('parent_id = 0');
			$query->order('lft DESC');
			$this->_db->setQuery($query, 0, 1);
			$reference = $this->_db->loadObject();
			
			if ($this->_db->getErrorNum()) {
				$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_MOVE_FAILED', get_class($this), $this->_db->getErrorMsg()));
				$this->setError($e);
				$this->_unlock();
				return false;
			}
			if ($this->_debug) {
				$this->_logtable(false);
			}
			
			if (! $repositionData = $this->_getTreeRepositionData($reference, $node->width, 'last-child')) {
				$this->_unlock();
				return false;
			}
		}
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('lft = lft + ' . (int) $node->width);
		$query->where($repositionData->left_where);
		$this->_db->setQuery($query);
		
		$this->_runQuery($query, 'MLIB_DATABASE_ERROR_MOVE_FAILED');
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('rgt = rgt + ' . (int) $node->width);
		$query->where($repositionData->right_where);
		$this->_db->setQuery($query);
		
		$this->_runQuery($query, 'MLIB_DATABASE_ERROR_MOVE_FAILED');
		
		$offset = $repositionData->new_lft - $node->lft;
		$levelOffset = $repositionData->new_level - $node->level;
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('rgt = ' . (int) $offset . ' - rgt');
		$query->set('lft = ' . (int) $offset . ' - lft');
		$query->set('level = level + ' . (int) $levelOffset);
		$query->where('lft < 0');
		$this->_db->setQuery($query);
		
		$this->_runQuery($query, 'MLIB_DATABASE_ERROR_MOVE_FAILED');
		
		if ($node->parent_id != $repositionData->new_parent_id) {
			$query = $this->_db->getQuery(true);
			$query->update($this->_tbl);
			
			if (property_exists($this, 'title') && $this->title !== null) {
				$query->set('title = ' . $this->_db->Quote($this->title));
			}
			if (property_exists($this, 'alias') && $this->alias !== null) {
				$query->set('alias = ' . $this->_db->Quote($this->alias));
			}
			
			$query->set('parent_id = ' . (int) $repositionData->new_parent_id);
			$query->where($this->_tbl_key . ' = ' . (int) $node->$k);
			$this->_db->setQuery($query);
			
			$this->_runQuery($query, 'MLIB_DATABASE_ERROR_MOVE_FAILED');
		}
		$this->_unlock();
		
		$this->parent_id = $repositionData->new_parent_id;
		$this->level = $repositionData->new_level;
		$this->lft = $repositionData->new_lft;
		$this->rgt = $repositionData->new_rgt;
		
		return true;
	}

	public function delete($pk = null, $children = true) {
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;
		
		if (! $this->_lock()) {
			return false;
		}
		
		if ($this->_trackAssets) {
			$name = $this->_getAssetName();
			$asset = MTable::getInstance('Asset');
			
			if (! $asset->_lock()) {
				return false;
			}
			
			if ($asset->loadByName($name)) {
				if (! $asset->delete(null, $children)) {
					$this->setError($asset->getError());
					$asset->_unlock();
					return false;
				}
				$asset->_unlock();
			}
			else {
				$this->setError($asset->getError());
				$asset->_unlock();
				return false;
			}
		}
		
		if (! $node = $this->_getNode($pk)) {
			$this->_unlock();
			return false;
		}
		
		if ($children) {
			$query = $this->_db->getQuery(true);
			$query->delete();
			$query->from($this->_tbl);
			$query->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
			$this->_runQuery($query, 'MLIB_DATABASE_ERROR_DELETE_FAILED');
			
			$query = $this->_db->getQuery(true);
			$query->update($this->_tbl);
			$query->set('lft = lft - ' . (int) $node->width);
			$query->where('lft > ' . (int) $node->rgt);
			$this->_runQuery($query, 'MLIB_DATABASE_ERROR_DELETE_FAILED');
			
			$query = $this->_db->getQuery(true);
			$query->update($this->_tbl);
			$query->set('rgt = rgt - ' . (int) $node->width);
			$query->where('rgt > ' . (int) $node->rgt);
			$this->_runQuery($query, 'MLIB_DATABASE_ERROR_DELETE_FAILED');
		}
		else {
			$query = $this->_db->getQuery(true);
			$query->delete();
			$query->from($this->_tbl);
			$query->where('lft = ' . (int) $node->lft);
			$this->_runQuery($query, 'MLIB_DATABASE_ERROR_DELETE_FAILED');
			
			$query = $this->_db->getQuery(true);
			$query->update($this->_tbl);
			$query->set('lft = lft - 1');
			$query->set('rgt = rgt - 1');
			$query->set('level = level - 1');
			$query->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
			$this->_runQuery($query, 'MLIB_DATABASE_ERROR_DELETE_FAILED');
			
			$query = $this->_db->getQuery(true);
			$query->update($this->_tbl);
			$query->set('parent_id = ' . (int) $node->parent_id);
			$query->where('parent_id = ' . (int) $node->$k);
			$this->_runQuery($query, 'MLIB_DATABASE_ERROR_DELETE_FAILED');
			
			$query = $this->_db->getQuery(true);
			$query->update($this->_tbl);
			$query->set('lft = lft - 2');
			$query->where('lft > ' . (int) $node->rgt);
			$this->_runQuery($query, 'MLIB_DATABASE_ERROR_DELETE_FAILED');
			
			$query = $this->_db->getQuery(true);
			$query->update($this->_tbl);
			$query->set('rgt = rgt - 2');
			$query->where('rgt > ' . (int) $node->rgt);
			$this->_runQuery($query, 'MLIB_DATABASE_ERROR_DELETE_FAILED');
		}
		
		$this->_unlock();
		
		return true;
	}

	public function check() {
		$this->parent_id = (int) $this->parent_id;
		if ($this->parent_id > 0) {
			$query = $this->_db->getQuery(true);
			$query->select('COUNT(' . $this->_tbl_key . ')');
			$query->from($this->_tbl);
			$query->where($this->_tbl_key . ' = ' . $this->parent_id);
			$this->_db->setQuery($query);
			
			if ($this->_db->loadResult()) {
				return true;
			}
			else {
				if ($this->_db->getErrorNum()) {
					$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_CHECK_FAILED', get_class($this), $this->_db->getErrorMsg()));
					$this->setError($e);
				}
				else {
					$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_INVALID_PARENT_ID', get_class($this)));
					$this->setError($e);
				}
			}
		}
		else {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_INVALID_PARENT_ID', get_class($this)));
			$this->setError($e);
		}
		
		return false;
	}

	public function store($updateNulls = false) {
		$k = $this->_tbl_key;
		
		if ($this->_debug) {
			echo "\n" . get_class($this) . "::store\n";
			$this->_logtable(true, false);
		}
		if (empty($this->$k)) {
			if ($this->_location_id >= 0) {
				if (! $this->_lock()) {
					return false;
				}
				
				if ($this->_location_id == 0) {
					$query = $this->_db->getQuery(true);
					$query->select($this->_tbl_key . ', parent_id, level, lft, rgt');
					$query->from($this->_tbl);
					$query->where('parent_id = 0');
					$query->order('lft DESC');
					$this->_db->setQuery($query, 0, 1);
					$reference = $this->_db->loadObject();
					
					if ($this->_db->getErrorNum()) {
						$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_STORE_FAILED', get_class($this), $this->_db->getErrorMsg()));
						$this->setError($e);
						$this->_unlock();
						return false;
					}
					if ($this->_debug) {
						$this->_logtable(false);
					}
				}
				else {
					if (! $reference = $this->_getNode($this->_location_id)) {
						$this->_unlock();
						return false;
					}
				}
				
				if (! ($repositionData = $this->_getTreeRepositionData($reference, 2, $this->_location))) {
					$this->_unlock();
					return false;
				}
				
				$query = $this->_db->getQuery(true);
				$query->update($this->_tbl);
				$query->set('lft = lft + 2');
				$query->where($repositionData->left_where);
				$this->_runQuery($query, 'MLIB_DATABASE_ERROR_STORE_FAILED');
				
				$query = $this->_db->getQuery(true);
				$query->update($this->_tbl);
				$query->set('rgt = rgt + 2');
				$query->where($repositionData->right_where);
				$this->_runQuery($query, 'MLIB_DATABASE_ERROR_STORE_FAILED');
				
				$this->parent_id = $repositionData->new_parent_id;
				$this->level = $repositionData->new_level;
				$this->lft = $repositionData->new_lft;
				$this->rgt = $repositionData->new_rgt;
			}
			else {
				$e = new MException(MText::_('MLIB_DATABASE_ERROR_INVALID_PARENT_ID'));
				$this->setError($e);
				return false;
			}
		}
		else {
			if ($this->_location_id > 0) {
				if (! $this->moveByReference($this->_location_id, $this->_location, $this->$k)) {
					return false;
				}
			}
			
			if (! $this->_lock()) {
				return false;
			}
		}
		
		if (! parent::store($updateNulls)) {
			$this->_unlock();
			return false;
		}
		if ($this->_debug) {
			$this->_logtable();
		}
		
		$this->_unlock();
		
		return true;
	}

	public function publish($pks = null, $state = 1, $userId = 0) {
		$k = $this->_tbl_key;
		
		MArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state = (int) $state;
		$compareState = ($state > 1) ? 1 : $state;
		
		if (empty($pks)) {
			if ($this->$k) {
				$pks = explode(',', $this->$k);
			}
			else {
				$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_NO_ROWS_SELECTED', get_class($this)));
				$this->setError($e);
				return false;
			}
		}
		
		$checkoutSupport = (property_exists($this, 'checked_out') || property_exists($this, 'checked_out_time'));
		
		foreach ($pks as $pk) {
			if (! $node = $this->_getNode($pk)) {
				return false;
			}
			
			if ($checkoutSupport) {
				$query = $this->_db->getQuery(true);
				$query->select('COUNT(' . $k . ')');
				$query->from($this->_tbl);
				$query->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
				$query->where('(checked_out <> 0 AND checked_out <> ' . (int) $userId . ')');
				$this->_db->setQuery($query);
				
				// Check for checked out children.
				if ($this->_db->loadResult()) {
					$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_CHILD_ROWS_CHECKED_OUT', get_class($this)));
					$this->setError($e);
					return false;
				}
			}
			
			if ($node->parent_id) {
				$query = $this->_db->getQuery(true)->select('n.' . $k)->from($this->_db->quoteName($this->_tbl) . ' AS n')->where('n.lft < ' . (int) $node->lft)->where('n.rgt > ' . (int) $node->rgt)->where('n.parent_id > 0')->where('n.published < ' . (int) $compareState);
				
				$this->_db->setQuery($query, 0, 1);
				
				$rows = $this->_db->loadColumn();
				
				if ($this->_db->getErrorNum()) {
					$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_PUBLISH_FAILED', get_class($this), $this->_db->getErrorMsg()));
					$this->setError($e);
					return false;
				}
				
				if (! empty($rows)) {
					$e = new MException(MText::_('MLIB_DATABASE_ERROR_ANCESTOR_NODES_LOWER_STATE'));
					$this->setError($e);
					return false;
				}
			}
			
			$query = $this->_db->getQuery(true)->update($this->_db->quoteName($this->_tbl))->set('published = ' . (int) $state)->where('(lft > ' . (int) $node->lft . ' AND rgt < ' . (int) $node->rgt . ')' . ' OR ' . $k . ' = ' . (int) $pk);
			$this->_db->setQuery($query);
			
			if (! $this->_db->execute()) {
				$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_PUBLISH_FAILED', get_class($this), $this->_db->getErrorMsg()));
				$this->setError($e);
				return false;
			}
			
			if ($checkoutSupport) {
				$this->checkin($pk);
			}
		}
		
		if (in_array($this->$k, $pks)) {
			$this->published = $state;
		}
		
		$this->setError('');
		return true;
	}

	public function orderUp($pk) {
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;
		
		if (! $this->_lock()) {
			return false;
		}
		
		if (! $node = $this->_getNode($pk)) {
			$this->_unlock();
			return false;
		}
		
		if (! $sibling = $this->_getNode($node->lft - 1, 'right')) {
			$this->_unlock();
			return false;
		}
		
		$query = $this->_db->getQuery(true);
		$query->select($this->_tbl_key);
		$query->from($this->_tbl);
		$query->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
		$this->_db->setQuery($query);
		$children = $this->_db->loadColumn();
		
		if ($this->_db->getErrorNum()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_ORDERUP_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			$this->_unlock();
			return false;
		}
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('lft = lft - ' . (int) $sibling->width);
		$query->set('rgt = rgt - ' . (int) $sibling->width);
		$query->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
		$this->_db->setQuery($query);
		
		if (! $this->_db->execute()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_ORDERUP_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			$this->_unlock();
			return false;
		}
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('lft = lft + ' . (int) $node->width);
		$query->set('rgt = rgt + ' . (int) $node->width);
		$query->where('lft BETWEEN ' . (int) $sibling->lft . ' AND ' . (int) $sibling->rgt);
		$query->where($this->_tbl_key . ' NOT IN (' . implode(',', $children) . ')');
		$this->_db->setQuery($query);
		
		if (! $this->_db->execute()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_ORDERUP_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			$this->_unlock();
			return false;
		}
		
		$this->_unlock();
		
		return true;
	}

	public function orderDown($pk) {
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;
		
		if (! $this->_lock()) {
			return false;
		}
		
		if (! $node = $this->_getNode($pk)) {
			$this->_unlock();
			return false;
		}
		
		if (! $sibling = $this->_getNode($node->rgt + 1, 'left')) {
			$query->unlock($this->_db);
			$this->_locked = false;
			return false;
		}
		
		$query = $this->_db->getQuery(true);
		$query->select($this->_tbl_key);
		$query->from($this->_tbl);
		$query->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
		$this->_db->setQuery($query);
		$children = $this->_db->loadColumn();
		
		if ($this->_db->getErrorNum()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_ORDERDOWN_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			$this->_unlock();
			return false;
		}
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('lft = lft + ' . (int) $sibling->width);
		$query->set('rgt = rgt + ' . (int) $sibling->width);
		$query->where('lft BETWEEN ' . (int) $node->lft . ' AND ' . (int) $node->rgt);
		$this->_db->setQuery($query);
		
		if (! $this->_db->execute()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_ORDERDOWN_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			$this->_unlock();
			return false;
		}
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('lft = lft - ' . (int) $node->width);
		$query->set('rgt = rgt - ' . (int) $node->width);
		$query->where('lft BETWEEN ' . (int) $sibling->lft . ' AND ' . (int) $sibling->rgt);
		$query->where($this->_tbl_key . ' NOT IN (' . implode(',', $children) . ')');
		$this->_db->setQuery($query);
		
		if (! $this->_db->execute()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_ORDERDOWN_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			$this->_unlock();
			return false;
		}
		
		$this->_unlock();
		
		return true;
	}

	public function getRootId() {
		$k = $this->_tbl_key;
		
		$query = $this->_db->getQuery(true);
		$query->select($k);
		$query->from($this->_tbl);
		$query->where('parent_id = 0');
		$this->_db->setQuery($query);
		
		$result = $this->_db->loadColumn();
		
		if ($this->_db->getErrorNum()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_GETROOTID_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			return false;
		}
		
		if (count($result) == 1) {
			$parentId = $result[0];
		}
		else {
			$query = $this->_db->getQuery(true);
			$query->select($k);
			$query->from($this->_tbl);
			$query->where('lft = 0');
			$this->_db->setQuery($query);
			
			$result = $this->_db->loadColumn();
			if ($this->_db->getErrorNum()) {
				$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_GETROOTID_FAILED', get_class($this), $this->_db->getErrorMsg()));
				$this->setError($e);
				return false;
			}
			
			if (count($result) == 1) {
				$parentId = $result[0];
			}
			elseif (property_exists($this, 'alias')) {
				// Test for a unique record alias = root
				$query = $this->_db->getQuery(true);
				$query->select($k);
				$query->from($this->_tbl);
				$query->where('alias = ' . $this->_db->quote('root'));
				$this->_db->setQuery($query);
				
				$result = $this->_db->loadColumn();
				if ($this->_db->getErrorNum()) {
					$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_GETROOTID_FAILED', get_class($this), $this->_db->getErrorMsg()));
					$this->setError($e);
					return false;
				}
				
				if (count($result) == 1) {
					$parentId = $result[0];
				}
				else {
					$e = new MException(MText::_('MLIB_DATABASE_ERROR_ROOT_NODE_NOT_FOUND'));
					$this->setError($e);
					return false;
				}
			}
			else {
				$e = new MException(MText::_('MLIB_DATABASE_ERROR_ROOT_NODE_NOT_FOUND'));
				$this->setError($e);
				return false;
			}
		}
		
		return $parentId;
	}

	public function rebuild($parentId = null, $leftId = 0, $level = 0, $path = '') {
		if ($parentId === null) {
			$parentId = $this->getRootId();
			if ($parentId === false) {
				return false;
			}
		}
		
		if (! isset($this->_cache['rebuild.sql'])) {
			$query = $this->_db->getQuery(true);
			$query->select($this->_tbl_key . ', alias');
			$query->from($this->_tbl);
			$query->where('parent_id = %d');
			
			if (property_exists($this, 'ordering')) {
				$query->order('parent_id, ordering, lft');
			}
			else {
				$query->order('parent_id, lft');
			}
			$this->_cache['rebuild.sql'] = (string) $query;
		}
		
		$this->_db->setQuery(sprintf($this->_cache['rebuild.sql'], (int) $parentId));
		$children = $this->_db->loadObjectList();
		
		$rightId = $leftId + 1;
		
		foreach ($children as $node) {
			$rightId = $this->rebuild($node->{$this->_tbl_key}, $rightId, $level + 1, $path . (empty($path) ? '' : '/') . $node->alias);
			
			if ($rightId === false) {
				return false;
			}
		}
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('lft = ' . (int) $leftId);
		$query->set('rgt = ' . (int) $rightId);
		$query->set('level = ' . (int) $level);
		$query->set('path = ' . $this->_db->quote($path));
		$query->where($this->_tbl_key . ' = ' . (int) $parentId);
		$this->_db->setQuery($query);
		
		if (! $this->_db->execute()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_REBUILD_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			return false;
		}
		
		return $rightId + 1;
	}

	public function rebuildPath($pk = null) {
		if (! property_exists($this, 'alias') || ! property_exists($this, 'path')) {
			return true;
		}
		
		$k = $this->_tbl_key;
		$pk = (is_null($pk)) ? $this->$k : $pk;
		
		$query = $this->_db->getQuery(true);
		$query->select('p.alias');
		$query->from($this->_tbl . ' AS n, ' . $this->_tbl . ' AS p');
		$query->where('n.lft BETWEEN p.lft AND p.rgt');
		$query->where('n.' . $this->_tbl_key . ' = ' . (int) $pk);
		$query->order('p.lft');
		$this->_db->setQuery($query);
		
		$segments = $this->_db->loadColumn();
		
		if ($segments[0] == 'root') {
			array_shift($segments);
		}
		
		$path = trim(implode('/', $segments), ' /\\');
		
		$query = $this->_db->getQuery(true);
		$query->update($this->_tbl);
		$query->set('path = ' . $this->_db->quote($path));
		$query->where($this->_tbl_key . ' = ' . (int) $pk);
		$this->_db->setQuery($query);
		
		if (! $this->_db->execute()) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_REBUILDPATH_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			return false;
		}
		
		$this->path = $path;
		
		return true;
	}

	public function saveorder($idArray = null, $lft_array = null) {
		if (is_array($idArray) && is_array($lft_array) && count($idArray) == count($lft_array)) {
			for ($i = 0, $count = count($idArray); $i < $count; $i ++) {
				$query = $this->_db->getQuery(true);
				$query->update($this->_tbl);
				$query->where($this->_tbl_key . ' = ' . (int) $idArray[$i]);
				$query->set('lft = ' . (int) $lft_array[$i]);
				$this->_db->setQuery($query);
				
				if (! $this->_db->execute()) {
					$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_REORDER_FAILED', get_class($this), $this->_db->getErrorMsg()));
					$this->setError($e);
					$this->_unlock();
					return false;
				}
				
				if ($this->_debug) {
					$this->_logtable();
				}
			}
			
			return $this->rebuild();
		}
		else {
			return false;
		}
	}

	protected function _getNode($id, $key = null) {
		switch ($key) {
			case 'parent' :
				$k = 'parent_id';
				break;
			case 'left' :
				$k = 'lft';
				break;
			case 'right' :
				$k = 'rgt';
				break;
			default :
				$k = $this->_tbl_key;
				break;
		}
		
		$query = $this->_db->getQuery(true);
		$query->select($this->_tbl_key . ', parent_id, level, lft, rgt');
		$query->from($this->_tbl);
		$query->where($k . ' = ' . (int) $id);
		$this->_db->setQuery($query, 0, 1);
		
		$row = $this->_db->loadObject();
		
		if ((! $row) || ($this->_db->getErrorNum())) {
			$e = new MException(MText::sprintf('MLIB_DATABASE_ERROR_GETNODE_FAILED', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			return false;
		}
		
		$row->numChildren = (int) ($row->rgt - $row->lft - 1) / 2;
		$row->width = (int) $row->rgt - $row->lft + 1;
		
		return $row;
	}

	protected function _getTreeRepositionData($referenceNode, $nodeWidth, $position = 'before') {
		if (! is_object($referenceNode) && isset($referenceNode->lft) && isset($referenceNode->rgt)) {
			return false;
		}
		
		if ($nodeWidth < 2) {
			return false;
		}
		
		$k = $this->_tbl_key;
		$data = new stdClass();
		
		switch ($position) {
			case 'first-child' :
				$data->left_where = 'lft > ' . $referenceNode->lft;
				$data->right_where = 'rgt >= ' . $referenceNode->lft;
				
				$data->new_lft = $referenceNode->lft + 1;
				$data->new_rgt = $referenceNode->lft + $nodeWidth;
				$data->new_parent_id = $referenceNode->$k;
				$data->new_level = $referenceNode->level + 1;
				break;
			
			case 'last-child' :
				$data->left_where = 'lft > ' . ($referenceNode->rgt);
				$data->right_where = 'rgt >= ' . ($referenceNode->rgt);
				
				$data->new_lft = $referenceNode->rgt;
				$data->new_rgt = $referenceNode->rgt + $nodeWidth - 1;
				$data->new_parent_id = $referenceNode->$k;
				$data->new_level = $referenceNode->level + 1;
				break;
			
			case 'before' :
				$data->left_where = 'lft >= ' . $referenceNode->lft;
				$data->right_where = 'rgt >= ' . $referenceNode->lft;
				
				$data->new_lft = $referenceNode->lft;
				$data->new_rgt = $referenceNode->lft + $nodeWidth - 1;
				$data->new_parent_id = $referenceNode->parent_id;
				$data->new_level = $referenceNode->level;
				break;
			
			default :
			case 'after' :
				$data->left_where = 'lft > ' . $referenceNode->rgt;
				$data->right_where = 'rgt > ' . $referenceNode->rgt;
				
				$data->new_lft = $referenceNode->rgt + 1;
				$data->new_rgt = $referenceNode->rgt + $nodeWidth;
				$data->new_parent_id = $referenceNode->parent_id;
				$data->new_level = $referenceNode->level;
				break;
		}
		
		if ($this->_debug) {
			echo "\nRepositioning Data for $position" . "\n-----------------------------------" . "\nLeft Where:    $data->left_where" . "\nRight Where:   $data->right_where" . "\nNew Lft:       $data->new_lft" . "\nNew Rgt:       $data->new_rgt" . "\nNew Parent ID: $data->new_parent_id" . "\nNew Level:     $data->new_level" . "\n";
		}
		
		return $data;
	}

	protected function _logtable($showData = true, $showQuery = true) {
		$sep = "\n" . str_pad('', 40, '-');
		$buffer = '';
		if ($showQuery) {
			$buffer .= "\n" . $this->_db->getQuery() . $sep;
		}
		
		if ($showData) {
			$query = $this->_db->getQuery(true);
			$query->select($this->_tbl_key . ', parent_id, lft, rgt, level');
			$query->from($this->_tbl);
			$query->order($this->_tbl_key);
			$this->_db->setQuery($query);
			
			$rows = $this->_db->loadRowList();
			$buffer .= sprintf("\n| %4s | %4s | %4s | %4s |", $this->_tbl_key, 'par', 'lft', 'rgt');
			$buffer .= $sep;
			
			foreach ($rows as $row) {
				$buffer .= sprintf("\n| %4s | %4s | %4s | %4s |", $row[0], $row[1], $row[2], $row[3]);
			}
			$buffer .= $sep;
		}
		echo $buffer;
	}

	protected function _runQuery($query, $errorMessage) {
		$this->_db->setQuery($query);
		
		if (! $this->_db->execute()) {
			$e = new MException(MText::sprintf('$errorMessage', get_class($this), $this->_db->getErrorMsg()));
			$this->setError($e);
			$this->_unlock();
			return false;
		}
		if ($this->_debug) {
			$this->_logtable();
		}
	}
}