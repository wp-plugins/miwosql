<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlGrid {

    public static function boolean($i, $value, $taskOn = null, $taskOff = null) {
        // Load the behavior.
        self::behavior();

        // Build the title.
        $title = ($value) ? MText::_('MYES') : MText::_('MNO');
        $title .= '::' . MText::_('MGLOBAL_CLICK_TO_TOGGLE_STATE');

        // Build the <a> tag.
        $bool   = ($value) ? 'true' : 'false';
        $task   = ($value) ? $taskOff : $taskOn;
        $toggle = (!$task) ? false : true;

        if ($toggle) {
            $html = '<a class="grid_' . $bool . ' hasTip" title="' . $title . '" rel="{id:\'cb' . $i . '\', task:\'' . $task
                . '\'}" href="#toggle"></a>';
        }
        else {
            $html = '<a class="grid_' . $bool . '"></a>';
        }

        return $html;
    }

    public static function sort($title, $order, $direction = 'asc', $selected = 0, $task = null, $new_direction = 'asc') {
        $direction = strtolower($direction);
        $images    = array('sort_asc.png', 'sort_desc.png');
        $index     = intval($direction == 'desc');

        if ($order != $selected) {
            $direction = $new_direction;
        }
        else {
            $direction = ($direction == 'desc') ? 'asc' : 'desc';
        }

        $html = '<a class="sortable" href="#" onclick="Miwi.tableOrdering(\'' . $order . '\',\'' . $direction . '\',\'' . $task . '\');return false;" title="'
            . MText::_('MGLOBAL_CLICK_TO_SORT_THIS_COLUMN') . '">';
        $html .= MText::_($title);

        if ($order == $selected) {
            $html .= MHtml::_('image', 'system/' . $images[$index], '', null, true);
        }

        $html .= '</a>';

        return $html;
    }

    public static function id($rowNum, $recId, $checkedOut = false, $name = 'cid') {
        if ($checkedOut) {
            return '';
        }
        else {
            return '<input type="checkbox" id="cb' . $rowNum . '" name="' . $name . '[]" value="' . $recId
            . '" onclick="Miwi.isChecked(this.checked);" title="' . MText::sprintf('MGRID_CHECKBOX_ROW_N', ($rowNum + 1)) . '" />';
        }
    }

    public static function access(&$row, $i, $archived = null) {
        // Deprecation warning.
        MLog::add('MGrid::access is deprecated.', MLog::WARNING, 'deprecated');

        // TODO: This needs to be reworked to suit the new access levels
        if ($row->access <= 1) {
            $color_access = 'class="allow"';
            $task_access  = 'accessregistered';
        }
        elseif ($row->access == 1) {
            $color_access = 'class="deny"';
            $task_access  = 'accessspecial';
        }
        else {
            $color_access = 'class="none"';
            $task_access  = 'accesspublic';
        }

        if ($archived == -1) {
            $href = MText::_($row->groupname);
        }
        else {
            $href = '
			<a href="javascript:void(0);" onclick="return listItemTask(\'cb' . $i . '\',\'' . $task_access . '\')" ' . $color_access . '>
			' . MText::_($row->groupname) . '</a>';
        }

        return $href;
    }

    public static function checkedOut(&$row, $i, $identifier = 'id') {
        $user   = MFactory::getUser();
        $userid = $user->get('id');

        $result = false;
        if ($row instanceof MTable) {
            $result = $row->isCheckedOut($userid);
        }
        else {
            $result = MTable::isCheckedOut($userid, $row->checked_out);
        }

        $checked = '';
        if ($result) {
            $checked = MHtmlGrid::_checkedOut($row);
        }
        else {
            if ($identifier == 'id') {
                $checked = MHtml::_('grid.id', $i, $row->$identifier);
            }
            else {
                $checked = MHtml::_('grid.id', $i, $row->$identifier, $result, $identifier);
            }
        }

        return $checked;
    }

    public static function published($value, $i, $img1 = 'tick.png', $img0 = 'publish_x.png', $prefix = '') {
        if (is_object($value)) {
            $value = $value->published;
        }

        $img    = $value ? $img1 : $img0;
        $task   = $value ? 'unpublish' : 'publish';
        $alt    = $value ? MText::_('MPUBLISHED') : MText::_('MUNPUBLISHED');
        $action = $value ? MText::_('MLIB_HTML_UNPUBLISH_ITEM') : MText::_('MLIB_HTML_PUBLISH_ITEM');

        $href = '
		<a href="#" onclick="return listItemTask(\'cb' . $i . '\',\'' . $prefix . $task . '\')" title="' . $action . '">'
            . MHtml::_('image', 'admin/' . $img, $alt, null, true) . '</a>';

        return $href;
    }

    public static function state($filter_state = '*', $published = 'Published', $unpublished = 'Unpublished', $archived = null, $trashed = null) {
        $state = array('' => '- ' . MText::_('MLIB_HTML_SELECT_STATE') . ' -', 'P' => MText::_($published), 'U' => MText::_($unpublished));

        if ($archived) {
            $state['A'] = MText::_($archived);
        }

        if ($trashed) {
            $state['T'] = MText::_($trashed);
        }

        return MHtml::_(
            'select.genericlist',
            $state,
            'filter_state',
            array(
                'list.attr'   => 'class="inputbox" size="1" onchange="Miwi.submitform();"',
                'list.select' => $filter_state,
                'option.key'  => null
            )
        );
    }

    public static function order($rows, $image = 'filesave.png', $task = 'saveorder') {
        // $image = MHtml::_('image','admin/'.$image, MText::_('MLIB_HTML_SAVE_ORDER'), NULL, true);
        $href = '<a href="javascript:saveorder(' . (count($rows) - 1) . ', \'' . $task . '\')" class="saveorder" title="'
            . MText::_('MLIB_HTML_SAVE_ORDER') . '"></a>';

        return $href;
    }

    protected static function _checkedOut(&$row, $overlib = 1) {
        $hover = '';

        if ($overlib) {
            $text = addslashes(htmlspecialchars($row->editor, ENT_COMPAT, 'UTF-8'));

            $date = MHtml::_('date', $row->checked_out_time, MText::_('DATE_FORMAT_LC1'));
            $time = MHtml::_('date', $row->checked_out_time, 'H:i');

            $hover = '<span class="editlinktip hasTip" title="' . MText::_('MLIB_HTML_CHECKED_OUT') . '::' . $text . '<br />' . $date . '<br />'
                . $time . '">';
        }

        $checked = $hover . MHtml::_('image', 'admin/checked_out.png', null, null, true) . '</span>';

        return $checked;
    }

    public static function behavior() {
        static $loaded;

        if (!$loaded) {
            // Build the behavior script.
            $js = '
		jQuery(document).ready(function () {
			actions = $$(\'a.move_up\');
			actions.combine($$(\'a.move_down\'));
			actions.combine($$(\'a.grid_true\'));
			actions.combine($$(\'a.grid_false\'));
			actions.combine($$(\'a.grid_trash\'));
			actions.each(function(a){
				a.addEvent(\'click\', function(){
					args = JSON.decode(this.rel);
					listItemTask(args.id, args.task);
				});
			});
			$$(\'input.check-all-toggle\').each(function(el){
				el.addEvent(\'click\', function(){
					if (el.checked) {
						document.id(this.form).getElements(\'input[type=checkbox]\').each(function(i){
							i.checked = true;
						})
					}
					else {
						document.id(this.form).getElements(\'input[type=checkbox]\').each(function(i){
							i.checked = false;
						})
					}
				});
			});
		});';

            // Add the behavior to the document head.
            $document = MFactory::getDocument();
            $document->addScriptDeclaration($js);

            $loaded = true;
        }
    }
}