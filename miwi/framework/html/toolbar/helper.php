<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('frameowrk.html.toolbar');

abstract class MToolBarHelper {

    public static function title($title, $icon = 'generic.png') {
        $bar = MToolBar::getInstance('toolbar');
        $bar->set('_title', $title);
    }

    public static function spacer($width = '') {
        $bar = MToolBar::getInstance('toolbar');
        // Add a spacer.
        $bar->appendButton('Separator', 'spacer', $width);
    }

    public static function divider() {
        $bar = MToolBar::getInstance('toolbar');
        // Add a divider.
        $bar->appendButton('Separator', 'divider');
    }

    public static function custom($task = '', $icon = '', $iconOver = '', $alt = '', $listSelect = true) {
        $bar = MToolBar::getInstance('toolbar');

        // Strip extension.
        $icon = preg_replace('#\.[^.]*$#', '', $icon);

        // Add a standard button.
        $bar->appendButton('Standard', $icon, $alt, $task, $listSelect);
    }

    public static function customX($task = '', $icon = '', $iconOver = '', $alt = '', $listSelect = true) {
        self::custom($task, $icon, $iconOver, $alt, $listSelect);
    }

    public static function preview($url = '', $updateEditors = false) {
        $bar = MToolBar::getInstance('toolbar');
        // Add a preview button.
        $bar->appendButton('Popup', 'preview', 'Preview', $url . '&task=preview');
    }

    public static function help($ref, $com = false, $override = null, $component = null) {
        $bar = MToolBar::getInstance('toolbar');
        // Add a help button.
        $bar->appendButton('Help', $ref, $com, $override, $component);
    }

    public static function back($alt = 'MTOOLBAR_BACK', $href = 'javascript:history.back();') {
        $bar = MToolBar::getInstance('toolbar');
        // Add a back button.
        $bar->appendButton('Link', 'back', $alt, $href);
    }

    public static function media_manager($directory = '', $alt = 'MTOOLBAR_UPLOAD') {
        $bar = MToolBar::getInstance('toolbar');
        // Add an upload button.
        $bar->appendButton('Popup', 'upload', $alt, 'index.php?option=com_media&tmpl=component&task=popupUpload&folder=' . $directory, 800, 520);
    }

    public static function makeDefault($task = 'default', $alt = 'MTOOLBAR_DEFAULT') {
        $bar = MToolBar::getInstance('toolbar');
        // Add a default button.
        $bar->appendButton('Standard', 'default', $alt, $task, true);
    }

    public static function assign($task = 'assign', $alt = 'MTOOLBAR_ASSIGN') {
        $bar = MToolBar::getInstance('toolbar');
        // Add an assign button.
        $bar->appendButton('Standard', 'assign', $alt, $task, true);
    }

    public static function addNew($task = 'add', $alt = 'MTOOLBAR_NEW', $check = false) {
        $bar = MToolBar::getInstance('toolbar');
        // Add a new button.
        $bar->appendButton('Standard', 'new', $alt, $task, $check);
    }

    public static function addNewX($task = 'add', $alt = 'MTOOLBAR_NEW') {
        self::addNew($task, $alt);
    }

    public static function publish($task = 'publish', $alt = 'MTOOLBAR_PUBLISH', $check = false) {
        $bar = MToolBar::getInstance('toolbar');
        // Add a publish button.
        $bar->appendButton('Standard', 'publish', $alt, $task, $check);
    }

    public static function publishList($task = 'publish', $alt = 'MTOOLBAR_PUBLISH') {
        $bar = MToolBar::getInstance('toolbar');
        // Add a publish button (list).
        $bar->appendButton('Standard', 'publish', $alt, $task, true);
    }

    public static function unpublish($task = 'unpublish', $alt = 'MTOOLBAR_UNPUBLISH', $check = false) {
        $bar = MToolBar::getInstance('toolbar');
        // Add an unpublish button
        $bar->appendButton('Standard', 'unpublish', $alt, $task, $check);
    }

    public static function unpublishList($task = 'unpublish', $alt = 'MTOOLBAR_UNPUBLISH') {
        $bar = MToolBar::getInstance('toolbar');
        // Add an unpublish button (list).
        $bar->appendButton('Standard', 'unpublish', $alt, $task, true);
    }

    public static function archiveList($task = 'archive', $alt = 'MTOOLBAR_ARCHIVE') {
        $bar = MToolBar::getInstance('toolbar');
        // Add an archive button.
        $bar->appendButton('Standard', 'archive', $alt, $task, true);
    }

    public static function unarchiveList($task = 'unarchive', $alt = 'MTOOLBAR_UNARCHIVE') {
        $bar = MToolBar::getInstance('toolbar');
        // Add an unarchive button (list).
        $bar->appendButton('Standard', 'unarchive', $alt, $task, true);
    }

    public static function editList($task = 'edit', $alt = 'MTOOLBAR_EDIT') {
        $bar = MToolBar::getInstance('toolbar');
        // Add an edit button.
        $bar->appendButton('Standard', 'edit', $alt, $task, true);
    }

    public static function editListX($task = 'edit', $alt = 'MTOOLBAR_EDIT') {
        self::editList($task, $alt);
    }

    public static function editHtml($task = 'edit_source', $alt = 'MTOOLBAR_EDIT_HTML') {
        $bar = MToolBar::getInstance('toolbar');
        // Add an edit html button.
        $bar->appendButton('Standard', 'edithtml', $alt, $task, true);
    }

    public static function editHtmlX($task = 'edit_source', $alt = 'MTOOLBAR_EDIT_HTML') {
        self::editHtml($task, $alt);
    }

    public static function editCss($task = 'edit_css', $alt = 'MTOOLBAR_EDIT_CSS') {
        $bar = MToolBar::getInstance('toolbar');
        // Add an edit css button (hide).
        $bar->appendButton('Standard', 'editcss', $alt, $task, true);
    }

    public static function editCssX($task = 'edit_css', $alt = 'MTOOLBAR_EDIT_CSS') {
        self::editCss($task, $alt);
    }

    public static function deleteList($msg = '', $task = 'remove', $alt = 'MTOOLBAR_DELETE') {
        $bar = MToolBar::getInstance('toolbar');
        // Add a delete button.
        if ($msg) {
            $bar->appendButton('Confirm', $msg, 'delete', $alt, $task, true);
        }
        else {
            $bar->appendButton('Standard', 'delete', $alt, $task, true);
        }
    }

    public static function deleteListX($msg = '', $task = 'remove', $alt = 'MTOOLBAR_DELETE') {
        self::deleteList($msg, $task, $alt);
    }

    public static function trash($task = 'remove', $alt = 'MTOOLBAR_TRASH', $check = true) {
        $bar = MToolBar::getInstance('toolbar');
        // Add a trash button.
        $bar->appendButton('Standard', 'trash', $alt, $task, $check, false);
    }

    public static function apply($task = 'apply', $alt = 'MTOOLBAR_APPLY') {
        $bar = MToolBar::getInstance('toolbar');
        // Add an apply button
        $bar->appendButton('Standard', 'apply', $alt, $task, false);
    }

    public static function save($task = 'save', $alt = 'MTOOLBAR_SAVE') {
        $bar = MToolBar::getInstance('toolbar');
        // Add a save button.
        $bar->appendButton('Standard', 'save', $alt, $task, false);
    }

    public static function save2new($task = 'save2new', $alt = 'MTOOLBAR_SAVE_AND_NEW') {
        $bar = MToolBar::getInstance('toolbar');
        // Add a save and create new button.
        $bar->appendButton('Standard', 'save-new', $alt, $task, false);
    }

    public static function save2copy($task = 'save2copy', $alt = 'MTOOLBAR_SAVE_AS_COPY') {
        $bar = MToolBar::getInstance('toolbar');
        // Add a save and create new button.
        $bar->appendButton('Standard', 'save-copy', $alt, $task, false);
    }

    public static function checkin($task = 'checkin', $alt = 'MTOOLBAR_CHECKIN', $check = true) {
        $bar = MToolBar::getInstance('toolbar');
        // Add a save and create new button.
        $bar->appendButton('Standard', 'checkin', $alt, $task, $check);
    }

    public static function cancel($task = 'cancel', $alt = 'MTOOLBAR_CANCEL') {
        $bar = MToolBar::getInstance('toolbar');
        // Add a cancel button.
        $bar->appendButton('Standard', 'cancel', $alt, $task, false);
    }

    public static function preferences($component, $height = '550', $width = '875', $alt = 'MToolbar_Options', $path = '', $onClose = '') {
    }
}

abstract class MSubMenuHelper {

    public static function addEntry($name, $link = '', $active = false) {
        $menu = MToolBar::getInstance('submenu');
        $menu->appendButton($name, $link, $active);
    }
}