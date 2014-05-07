<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.filesystem.file');
mimport('framework.filesystem.folder');

class MFormFieldModuleLayout extends MFormField {

    protected $type = 'ModuleLayout';

    protected function getInput() {
        // Get the client id.
        $clientId = $this->element['client_id'];

        if (is_null($clientId) && $this->form instanceof MForm) {
            $clientId = $this->form->getValue('client_id');
        }
        $clientId = (int)$clientId;

        $client = MApplicationHelper::getClientInfo($clientId);

        // Get the module.
        $module = (string)$this->element['module'];

        if (empty($module) && ($this->form instanceof MForm)) {
            $module = $this->form->getValue('module');
        }

        $module = preg_replace('#\W#', '', $module);

        // Get the template.
        $template = (string)$this->element['template'];
        $template = preg_replace('#\W#', '', $template);

        // Get the style.
        if ($this->form instanceof MForm) {
            $template_style_id = $this->form->getValue('template_style_id');
        }

        $template_style_id = preg_replace('#\W#', '', $template_style_id);

        // If an extension and view are present build the options.
        if ($module && $client) {

            // Load language file
            $lang = MFactory::getLanguage();
            $lang->load($module . '.sys', $client->path, null, false, false)
            || $lang->load($module . '.sys', $client->path . '/modules/' . $module, null, false, false)
            || $lang->load($module . '.sys', $client->path, $lang->getDefault(), false, false)
            || $lang->load($module . '.sys', $client->path . '/modules/' . $module, $lang->getDefault(), false, false);

            // Get the database object and a new query object.
            $db    = MFactory::getDBO();
            $query = $db->getQuery(true);

            // Build the query.
            $query->select('element, name');
            $query->from('#__extensions as e');
            $query->where('e.client_id = ' . (int)$clientId);
            $query->where('e.type = ' . $db->quote('template'));
            $query->where('e.enabled = 1');

            if ($template) {
                $query->where('e.element = ' . $db->quote($template));
            }

            if ($template_style_id) {
                $query->join('LEFT', '#__template_styles as s on s.template=e.element');
                $query->where('s.id=' . (int)$template_style_id);
            }

            // Set the query and load the templates.
            $db->setQuery($query);
            $templates = $db->loadObjectList('element');

            // Check for a database error.
            if ($db->getErrorNum()) {
                MError::raiseWarning(500, $db->getErrorMsg());
            }

            // Build the search paths for module layouts.
            $module_path = MPath::clean($client->path . '/modules/' . $module . '/tmpl');

            // Prepare array of component layouts
            $module_layouts = array();

            // Prepare the grouped list
            $groups = array();

            // Add the layout options from the module path.
            if (is_dir($module_path) && ($module_layouts = MFolder::files($module_path, '^[^_]*\.php$'))) {
                // Create the group for the module
                $groups['_']          = array();
                $groups['_']['id']    = $this->id . '__';
                $groups['_']['text']  = MText::sprintf('MOPTION_FROM_MODULE');
                $groups['_']['items'] = array();

                foreach ($module_layouts as $file) {
                    // Add an option to the module group
                    $value                  = MFile::stripExt($file);
                    $text                   = $lang->hasKey($key = strtoupper($module . '_LAYOUT_' . $value)) ? MText::_($key) : $value;
                    $groups['_']['items'][] = MHtml::_('select.option', '_:' . $value, $text);
                }
            }

            // Loop on all templates
            if ($templates) {
                foreach ($templates as $template) {
                    // Load language file
                    $lang->load('tpl_' . $template->element . '.sys', $client->path, null, false, false)
                    || $lang->load('tpl_' . $template->element . '.sys', $client->path . '/templates/' . $template->element, null, false, false)
                    || $lang->load('tpl_' . $template->element . '.sys', $client->path, $lang->getDefault(), false, false)
                    || $lang->load(
                        'tpl_' . $template->element . '.sys', $client->path . '/templates/' . $template->element, $lang->getDefault(),
                        false, false
                    );

                    $template_path = MPath::clean($client->path . '/templates/' . $template->element . '/html/' . $module);

                    // Add the layout options from the template path.
                    if (is_dir($template_path) && ($files = MFolder::files($template_path, '^[^_]*\.php$'))) {
                        foreach ($files as $i => $file) {
                            // Remove layout that already exist in component ones
                            if (in_array($file, $module_layouts)) {
                                unset($files[$i]);
                            }
                        }

                        if (count($files)) {
                            // Create the group for the template
                            $groups[$template->element]          = array();
                            $groups[$template->element]['id']    = $this->id . '_' . $template->element;
                            $groups[$template->element]['text']  = MText::sprintf('MOPTION_FROM_TEMPLATE', $template->name);
                            $groups[$template->element]['items'] = array();

                            foreach ($files as $file) {
                                // Add an option to the template group
                                $value                                 = MFile::stripExt($file);
                                $text                                  = $lang->hasKey($key = strtoupper('TPL_' . $template->element . '_' . $module . '_LAYOUT_' . $value))
                                    ? MText::_($key) : $value;
                                $groups[$template->element]['items'][] = MHtml::_('select.option', $template->element . ':' . $value, $text);
                            }
                        }
                    }
                }
            }
            // Compute attributes for the grouped list
            $attr = $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';

            // Prepare HTML code
            $html = array();

            // Compute the current selected values
            $selected = array($this->value);

            // Add a grouped list
            $html[] = MHtml::_(
                'select.groupedlist', $groups, $this->name,
                array('id' => $this->id, 'group.id' => 'id', 'list.attr' => $attr, 'list.select' => $selected)
            );

            return implode($html);
        }
        else {

            return '';
        }
    }
}
