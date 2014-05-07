<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

MFormHelper::loadFieldClass('list');

class MFormFieldCategory extends MFormFieldList {

    public $type = 'Category';

    protected function getOptions() {
        // Initialise variables.
        $options   = array();
        $extension = $this->element['extension'] ? (string)$this->element['extension'] : (string)$this->element['scope'];
        $published = (string)$this->element['published'];
        $name      = (string)$this->element['name'];

        // Load the category options for a given extension.
        if (!empty($extension)) {

            // Filter over published state or not depending upon if it is present.
            if ($published) {
                $options = MHtml::_('category.options', $extension, array('filter.published' => explode(',', $published)));
            }
            else {
                $options = MHtml::_('category.options', $extension);
            }

            // Verify permissions.  If the action attribute is set, then we scan the options.
            if ((string)$this->element['action']) {

                // Get the current user object.
                $user = MFactory::getUser();

                // For new items we want a list of categories you are allowed to create in.
                if (!$this->form->getValue($name)) {
                    foreach ($options as $i => $option) {
                        // To take save or create in a category you need to have create rights for that category
                        // unless the item is already in that category.
                        // Unset the option if the user isn't authorised for it. In this field assets are always categories.
                        if ($user->authorise('core.create', $extension . '.category.' . $option->value) != true) {
                            unset($options[$i]);
                        }
                    }
                }
                // If you have an existing category id things are more complex.
                else {
                    $categoryOld = $this->form->getValue($name);
                    foreach ($options as $i => $option) {
                        // If you are only allowed to edit in this category but not edit.state, you should not get any
                        // option to change the category.
                        if ($user->authorise('core.edit.state', $extension . '.category.' . $categoryOld) != true) {
                            if ($option->value != $categoryOld) {
                                unset($options[$i]);
                            }
                        }
                        // However, if you can edit.state you can also move this to another category for which you have
                        // create permission and you should also still be able to save in the current category.
                        elseif
                        (($user->authorise('core.create', $extension . '.category.' . $option->value) != true)
                            && $option->value != $categoryOld
                        ) {
                            unset($options[$i]);
                        }
                    }
                }
            }

            if (isset($this->element['show_root'])) {
                array_unshift($options, MHtml::_('select.option', '0', MText::_('MGLOBAL_ROOT')));
            }
        }
        else {
            MError::raiseWarning(500, MText::_('MLIB_FORM_ERROR_FIELDS_CATEGORY_ERROR_EXTENSION_EMPTY'));
        }

        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}
