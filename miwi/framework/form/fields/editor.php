<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.html.editor');

class MFormFieldEditor extends MFormField {

    public $type = 'Editor';

    protected $editor;

    protected function getInput() {
        // Initialize some field attributes.
        $rows        = (int)$this->element['rows'];
        $cols        = (int)$this->element['cols'];
        $height      = ((string)$this->element['height']) ? (string)$this->element['height'] : '250';
        $width       = ((string)$this->element['width']) ? (string)$this->element['width'] : '100%';
        $assetField  = $this->element['asset_field'] ? (string)$this->element['asset_field'] : 'asset_id';
        $authorField = $this->element['created_by_field'] ? (string)$this->element['created_by_field'] : 'created_by';
        $asset       = $this->form->getValue($assetField) ? $this->form->getValue($assetField) : (string)$this->element['asset_id'];

        // Build the buttons array.
        $buttons = (string)$this->element['buttons'];

        if ($buttons == 'true' || $buttons == 'yes' || $buttons == '1') {
            $buttons = true;
        }
        elseif ($buttons == 'false' || $buttons == 'no' || $buttons == '0') {
            $buttons = false;
        }
        else {
            $buttons = explode(',', $buttons);
        }

        $hide = ((string)$this->element['hide']) ? explode(',', (string)$this->element['hide']) : array();

        // Get an editor object.
        $editor = $this->getEditor();

        return $editor
            ->display(
                $this->name, htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8'), $width, $height, $cols, $rows,
                $buttons ? (is_array($buttons) ? array_merge($buttons, $hide) : $hide) : false, $this->id, $asset,
                $this->form->getValue($authorField)
            );
    }

    protected function &getEditor() {
        // Only create the editor if it is not already created.
        if (empty($this->editor)) {
            // Initialize variables.
            $editor = null;

            // Get the editor type attribute. Can be in the form of: editor="desired|alternative".
            $type = trim((string)$this->element['editor']);

            if ($type) {
                // Get the list of editor types.
                $types = explode('|', $type);

                // Get the database object.
                $db = MFactory::getDBO();

                // Iterate over teh types looking for an existing editor.
                foreach ($types as $element) {
                    // Build the query.
                    $query = $db->getQuery(true);
                    $query->select('element');
                    $query->from('#__extensions');
                    $query->where('element = ' . $db->quote($element));
                    $query->where('folder = ' . $db->quote('editors'));
                    $query->where('enabled = 1');

                    // Check of the editor exists.
                    $db->setQuery($query, 0, 1);
                    $editor = $db->loadResult();

                    // If an editor was found stop looking.
                    if ($editor) {
                        break;
                    }
                }
            }

            // Create the MEditor instance based on the given editor.
            $this->editor = MFactory::getEditor($editor ? $editor : null);
        }

        return $this->editor;
    }

    public function save() {
        return $this->getEditor()->save($this->id);
    }
}
