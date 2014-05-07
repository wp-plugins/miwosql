<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MPagination extends MObject {

	public $limitstart = null;
	public $limit = null;
	public $total = null;
	public $prefix = null;
	protected $_viewall = false;
	protected $_additionalUrlParams = array();

	public function __construct($total, $limitstart, $limit, $prefix = '') {
		// Value/type checking.
		$this->total = (int) $total;
		$this->limitstart = (int) max($limitstart, 0);
		$this->limit = (int) max($limit, 0);
		$this->prefix = $prefix;

		if ($this->limit > $this->total) {
			$this->limitstart = 0;
		}

		if (!$this->limit) {
			$this->limit = $total;
			$this->limitstart = 0;
		}

		if ($this->limitstart > $this->total - $this->limit) {
			$this->limitstart = max(0, (int) (ceil($this->total / $this->limit) - 1) * $this->limit);
		}

		// Set the total pages and current page values.
		if ($this->limit > 0) {
			$this->set('pages.total', ceil($this->total / $this->limit));
			$this->set('pages.current', ceil(($this->limitstart + 1) / $this->limit));
		}

		// Set the pagination iteration loop values.
		$displayedPages = 10;
		$this->set('pages.start', $this->get('pages.current') - ($displayedPages / 2));
		if ($this->get('pages.start') < 1) {
			$this->set('pages.start', 1);
		}
		
		if (($this->get('pages.start') + $displayedPages) > $this->get('pages.total')) {
			$this->set('pages.stop', $this->get('pages.total'));
			
			if ($this->get('pages.total') < $displayedPages) {
				$this->set('pages.start', 1);
			}
			else {
				$this->set('pages.start', $this->get('pages.total') - $displayedPages + 1);
			}
		}
		else {
			$this->set('pages.stop', ($this->get('pages.start') + $displayedPages - 1));
		}

		// If we are viewing all records set the view all flag to true.
		if ($limit == 0) {
			$this->_viewall = true;
		}
	}

	public function setAdditionalUrlParam($key, $value) {
		// Get the old value to return and set the new one for the URL parameter.
		$result = isset($this->_additionalUrlParams[$key]) ? $this->_additionalUrlParams[$key] : null;

		// If the passed parameter value is null unset the parameter, otherwise set it to the given value.
		if ($value === null) {
			unset($this->_additionalUrlParams[$key]);
		}
		else {
			$this->_additionalUrlParams[$key] = $value;
		}

		return $result;
	}

	public function getAdditionalUrlParam($key) {
		$result = isset($this->_additionalUrlParams[$key]) ? $this->_additionalUrlParams[$key] : null;

		return $result;
	}

	public function getRowOffset($index) {
		return $index + 1 + $this->limitstart;
	}

	public function getData() {
		static $data;
		
		if (!is_object($data)) {
			$data = $this->_buildDataObject();
		}
		
		return $data;
	}

	public function getPagesCounter() {
		// Initialise variables.
		$html = null;
		
		if ($this->get('pages.total') > 1) {
			$html .= MText::sprintf('MLIB_HTML_PAGE_CURRENT_OF_TOTAL', $this->get('pages.current'), $this->get('pages.total'));
		}
		
		return $html;
	}

	public function getResultsCounter() {
		// Initialise variables.
		$html = null;
		$fromResult = $this->limitstart + 1;

		// If the limit is reached before the end of the list.
		if ($this->limitstart + $this->limit < $this->total) {
			$toResult = $this->limitstart + $this->limit;
		}
		else {
			$toResult = $this->total;
		}

		// If there are results found.
		if ($this->total > 0) {
			$msg = MText::sprintf('MLIB_HTML_RESULTS_OF', $fromResult, $toResult, $this->total);
			$html .= "\n" . $msg;
		}
		else {
			$html .= "\n" . MText::_('MLIB_HTML_NO_RECORDS_FOUND');
		}

		return $html;
	}

	public function getPagesLinks($pagescounter) {
		$app = MFactory::getApplication();

		// Build the page navigation list.
		$data = $this->_buildDataObject();

		$list = array();
		$list['prefix'] = $this->prefix;
		$list['pagescounter'] = $pagescounter;

		$itemOverride = false;
		$listOverride = false;

		$chromePath = MPATH_THEMES . '/' . $app->getTemplate() . '/html/pagination.php';
		if (file_exists($chromePath)) {
			include_once $chromePath;
			if (function_exists('pagination_item_active') && function_exists('pagination_item_inactive')) {
				$itemOverride = true;
			}
			
			if (function_exists('pagination_list_render')) {
				$listOverride = true;
			}
		}

		// Build the select list
		if ($data->all->base !== null) {
			$list['all']['active'] = true;
			$list['all']['data'] = ($itemOverride) ? pagination_item_active($data->all) : $this->_item_active($data->all);
		}
		else {
			$list['all']['active'] = false;
			$list['all']['data'] = ($itemOverride) ? pagination_item_inactive($data->all) : $this->_item_inactive($data->all);
		}

		if ($data->start->base !== null) {
			$list['start']['active'] = true;
			$list['start']['data'] = ($itemOverride) ? pagination_item_active($data->start) : $this->_item_active($data->start);
		}
		else {
			$list['start']['active'] = false;
			$list['start']['data'] = ($itemOverride) ? pagination_item_inactive($data->start) : $this->_item_inactive($data->start);
		}
		
		if ($data->previous->base !== null) {
			$list['previous']['active'] = true;
			$list['previous']['data'] = ($itemOverride) ? pagination_item_active($data->previous) : $this->_item_active($data->previous);
		}
		else {
			$list['previous']['active'] = false;
			$list['previous']['data'] = ($itemOverride) ? pagination_item_inactive($data->previous) : $this->_item_inactive($data->previous);
		}

		$list['pages'] = array(); //make sure it exists
		foreach ($data->pages as $i => $page) {
			if ($page->base !== null) {
				$list['pages'][$i]['active'] = true;
				$list['pages'][$i]['data'] = ($itemOverride) ? pagination_item_active($page) : $this->_item_active($page);
			}
			else {
				$list['pages'][$i]['active'] = false;
				$list['pages'][$i]['data'] = ($itemOverride) ? pagination_item_inactive($page) : $this->_item_inactive($page);
			}
		}

		if ($data->next->base !== null) {
			$list['next']['active'] = true;
			$list['next']['data'] = ($itemOverride) ? pagination_item_active($data->next) : $this->_item_active($data->next);
		}
		else {
			$list['next']['active'] = false;
			$list['next']['data'] = ($itemOverride) ? pagination_item_inactive($data->next) : $this->_item_inactive($data->next);
		}

		if ($data->end->base !== null) {
			$list['end']['active'] = true;
			$list['end']['data'] = ($itemOverride) ? pagination_item_active($data->end) : $this->_item_active($data->end);
		}
		else {
			$list['end']['active'] = false;
			$list['end']['data'] = ($itemOverride) ? pagination_item_inactive($data->end) : $this->_item_inactive($data->end);
		}

		if ($this->total > $this->limit) {
			return ($listOverride) ? pagination_list_render($list) : $this->_list_render($list);
		}
		else {
			return '';
		}
	}

	public function getListFooter() {
		$app = MFactory::getApplication();

		$list = array();
		$list['prefix'] = $this->prefix;
		$list['limit'] = $this->limit;
		$list['limitstart'] = $this->limitstart;
		$list['total'] = $this->total;
		$list['limitfield'] = $this->getLimitBox();
		$list['pagescounter'] = $this->getPagesCounter();
		$list['pageslinks'] = $this->getPagesLinks($list['pagescounter']);

		$chromePath = MPATH_THEMES . '/' . $app->getTemplate() . '/html/pagination.php';
		if (file_exists($chromePath)) {
			include_once $chromePath;
			
			if (function_exists('pagination_list_footer')) {
				return pagination_list_footer($list);
			}
		}
		
		return $this->_list_footer($list);
	}

	public function getLimitBox() {
		$app = MFactory::getApplication();

		// Initialise variables.
		$limits = array();

		// Make the option list.
		for ($i = 5; $i <= 30; $i += 5) {
			$limits[] = MHtml::_('select.option', "$i");
		}
		$limits[] = MHtml::_('select.option', '50', MText::_('M50'));
		$limits[] = MHtml::_('select.option', '100', MText::_('M100'));
		$limits[] = MHtml::_('select.option', '0', MText::_('MALL'));

		$selected = $this->_viewall ? 0 : $this->limit;

		// Build the select list.
		if ($app->isAdmin())
		{
			$html = MHtml::_(
				'select.genericlist',
				$limits,
				$this->prefix . 'limit',
				'class="inputbox" size="1" onchange="Miwi.submitform();"',
				'value',
				'text',
				$selected
			);
		}
		else
		{
			$html = MHtml::_(
				'select.genericlist',
				$limits,
				$this->prefix . 'limit',
				'class="inputbox" size="1" onchange="this.form.submit()"',
				'value',
				'text',
				$selected
			);
		}
		return $html;
	}

	public function orderUpIcon($i, $condition = true, $task = 'orderup', $alt = 'MLIB_HTML_MOVE_UP', $enabled = true, $checkbox = 'cb') {
		if (($i > 0 || ($i + $this->limitstart > 0)) && $condition)
		{
			return MHtml::_('mgrid.orderUp', $i, $task, '', $alt, $enabled, $checkbox);
		}
		else
		{
			return '&#160;';
		}
	}

	public function orderDownIcon($i, $n, $condition = true, $task = 'orderdown', $alt = 'MLIB_HTML_MOVE_DOWN', $enabled = true, $checkbox = 'cb') {
		if (($i < $n - 1 || $i + $this->limitstart < $this->total - 1) && $condition) {
			return MHtml::_('mgrid.orderDown', $i, $task, '', $alt, $enabled, $checkbox);
		}
		else {
			return '&#160;';
		}
	}

	protected function _list_footer($list) {
		$html = "<div class=\"list-footer\">\n";

		$html .= "\n<div class=\"limit\">" . MText::_('MGLOBAL_DISPLAY_NUM') . $list['limitfield'] . "</div>";
		$html .= $list['pageslinks'];

		$html .= "\n<input type=\"hidden\" name=\"" . $list['prefix'] . "limitstart\" value=\"" . $list['limitstart'] . "\" />";
		$html .= "\n</div>";

		return $html;
	}

	protected function _list_render($list) {
		// Reverse output rendering for right-to-left display.
		$html = '<ul class="tablenav">';
		$html .= '<li class="pagination-start">' . $list['start']['data'] . '</li>';
		$html .= '<li class="pagination-prev">' . $list['previous']['data'] . '</li>';

		$html .= "<div class=\"paging-input\">" . $list['pagescounter'] . "</div>";
		
		$html .= '<li class="pagination-next">' . $list['next']['data'] . '</li>';
		$html .= '<li class="pagination-end">' . $list['end']['data'] . '</li>';
		$html .= '</ul>';

		return $html;
	}

	protected function _item_active(&$item) {
		$app = MFactory::getApplication();
		
		if ($app->isAdmin()) {
			if ($item->base > 0) {
				return "<a  class=\"pagination_link active\" title=\"" . $item->text . "\" onclick=\"document.adminForm." . $this->prefix . "limitstart.value=" . $item->base
					. "; Miwi.submitform();return false;\">" . $item->text . "</a>";
			}
			else {
				return "<a class=\"pagination_link active\" title=\"" . $item->text . "\" onclick=\"document.adminForm." . $this->prefix
					. "limitstart.value=0; Miwi.submitform();return false;\">" . $item->text . "</a>";
			}
		}
		else {
			return "<a title=\"" . $item->text . "\" href=\"" . $item->link . "\" class=\"pagenav\">" . $item->text . "</a>";
		}
	}

	protected function _item_inactive(&$item) {
		$app = MFactory::getApplication();
		
		if ($app->isAdmin()) {
			return "<span class=\"pagination_link\">" . $item->text . "</span>";
		}
		else {
			return "<span class=\"pagenav\">" . $item->text . "</span>";
		}
	}

	protected function _buildDataObject() {
		// Initialise variables.
		$data = new stdClass;

		// Build the additional URL parameters string.
		$params = '';
		if (!empty($this->_additionalUrlParams)) {
			foreach ($this->_additionalUrlParams as $key => $value) {
				$params .= '&' . $key . '=' . $value;
			}
		}

		$data->all = new MPaginationObject(MText::_('MLIB_HTML_VIEW_ALL'), $this->prefix);
		if (!$this->_viewall) {
			$data->all->base = '0';
			$data->all->link = MRoute::_($params . '&' . $this->prefix . 'limitstart=');
		}

		// Set the start and previous data objects.
		$data->start = new MPaginationObject(MText::_('MLIB_HTML_START'), $this->prefix);
		$data->previous = new MPaginationObject(MText::_('MPREV'), $this->prefix);

		if ($this->get('pages.current') > 1) {
			$page = ($this->get('pages.current') - 2) * $this->limit;

			// Set the empty for removal from route
			//$page = $page == 0 ? '' : $page;

			$data->start->base = '0';
			$data->start->link = MRoute::_($params . '&' . $this->prefix . 'limitstart=0');
			$data->previous->base = $page;
			$data->previous->link = MRoute::_($params . '&' . $this->prefix . 'limitstart=' . $page);
		}

		// Set the next and end data objects.
		$data->next = new MPaginationObject(MText::_('MNEXT'), $this->prefix);
		$data->end = new MPaginationObject(MText::_('MLIB_HTML_END'), $this->prefix);

		if ($this->get('pages.current') < $this->get('pages.total')) {
			$next = $this->get('pages.current') * $this->limit;
			$end = ($this->get('pages.total') - 1) * $this->limit;

			$data->next->base = $next;
			$data->next->link = MRoute::_($params . '&' . $this->prefix . 'limitstart=' . $next);
			$data->end->base = $end;
			$data->end->link = MRoute::_($params . '&' . $this->prefix . 'limitstart=' . $end);
		}

		$data->pages = array();
		$stop = $this->get('pages.stop');
		for ($i = $this->get('pages.start'); $i <= $stop; $i++) {
			$offset = ($i - 1) * $this->limit;
			// Set the empty for removal from route
			//$offset = $offset == 0 ? '' : $offset;

			$data->pages[$i] = new MPaginationObject($i, $this->prefix);
			if ($i != $this->get('pages.current') || $this->_viewall) {
				$data->pages[$i]->base = $offset;
				$data->pages[$i]->link = MRoute::_($params . '&' . $this->prefix . 'limitstart=' . $offset);
			}
		}
		return $data;
	}
}

class MPaginationObject extends MObject {

	public $text;
	public $base;
	public $link;
	public $prefix;

	public function __construct($text, $prefix = '', $base = null, $link = null) {
		$this->text = $text;
		$this->prefix = $prefix;
		$this->base = $base;
		$this->link = $link;
	}
}