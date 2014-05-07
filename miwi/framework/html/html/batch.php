<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlBatch {

    public static function access() {
        // Create the batch selector to change an access level on a selection list.
        $lines = array(
            '<label id="batch-access-lbl" for="batch-access" class="hasTip" title="' . MText::_('MLIB_HTML_BATCH_ACCESS_LABEL') . '::'
            . MText::_('MLIB_HTML_BATCH_ACCESS_LABEL_DESC') . '">', MText::_('MLIB_HTML_BATCH_ACCESS_LABEL'), '</label>',
            MHtml::_(
                'access.assetgrouplist',
                'batch[assetgroup_id]', '',
                'class="inputbox"',
                array(
                    'title' => MText::_('MLIB_HTML_BATCH_NOCHANGE'),
                    'id'    => 'batch-access')
            )
        );

        return implode("\n", $lines);
    }

    public static function item($extension) {
        // Create the copy/move options.
        $options = array(MHtml::_('select.option', 'c', MText::_('MLIB_HTML_BATCH_COPY')),
            MHtml::_('select.option', 'm', MText::_('MLIB_HTML_BATCH_MOVE')));

        // Create the batch selector to change select the category by which to move or copy.
        $lines = array('<label id="batch-choose-action-lbl" for="batch-choose-action">', MText::_('MLIB_HTML_BATCH_MENU_LABEL'), '</label>',
            '<fieldset id="batch-choose-action" class="combo">', '<select name="batch[category_id]" class="inputbox" id="batch-category-id">',
            '<option value="">' . MText::_('MSELECT') . '</option>',
            MHtml::_('select.options', MHtml::_('category.options', $extension)), '</select>',
            MHtml::_('select.radiolist', $options, 'batch[move_copy]', '', 'value', 'text', 'm'), '</fieldset>');

        return implode("\n", $lines);
    }

    public static function language() {
        // Create the batch selector to change the language on a selection list.
        $lines = array(
            '<label id="batch-language-lbl" for="batch-language" class="hasTip"'
            . ' title="' . MText::_('MLIB_HTML_BATCH_LANGUAGE_LABEL') . '::' . MText::_('MLIB_HTML_BATCH_LANGUAGE_LABEL_DESC') . '">',
            MText::_('MLIB_HTML_BATCH_LANGUAGE_LABEL'),
            '</label>',
            '<select name="batch[language_id]" class="inputbox" id="batch-language-id">',
            '<option value="">' . MText::_('MLIB_HTML_BATCH_LANGUAGE_NOCHANGE') . '</option>',
            MHtml::_('select.options', MHtml::_('contentlanguage.existing', true, true), 'value', 'text'),
            '</select>'
        );

        return implode("\n", $lines);
    }

    public static function user($noUser = true) {
        $optionNo = '';
        if ($noUser) {
            $optionNo = '<option value="0">' . MText::_('MLIB_HTML_BATCH_USER_NOUSER') . '</option>';
        }

        // Create the batch selector to select a user on a selection list.
        $lines = array(
            '<label id="batch-user-lbl" for="batch-user" class="hasTip"'
            . ' title="' . MText::_('MLIB_HTML_BATCH_USER_LABEL') . '::' . MText::_('MLIB_HTML_BATCH_USER_LABEL_DESC') . '">',
            MText::_('MLIB_HTML_BATCH_USER_LABEL'),
            '</label>',
            '<select name="batch[user_id]" class="inputbox" id="batch-user-id">',
            '<option value="">' . MText::_('MLIB_HTML_BATCH_USER_NOCHANGE') . '</option>',
            $optionNo,
            MHtml::_('select.options', MHtml::_('user.userlist'), 'value', 'text'),
            '</select>'
        );

        return implode("\n", $lines);
    }
}