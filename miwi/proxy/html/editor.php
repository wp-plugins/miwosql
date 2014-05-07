<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MEditor extends MObject {

    public function display($name, $html, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array()) {
        $id = str_replace('][', '-',$name);
        $id = str_replace('[', '-',$id);
        $id = str_replace(']', '',$id);

        $settings = array( 'textarea_name' => $name);

        $html = htmlspecialchars_decode($html);

        wp_editor($html, $id, $settings);
    }

	public function save($editor) {
		return null;
	}
}