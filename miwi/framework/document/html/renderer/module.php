<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDocumentRendererModule extends MDocumentRenderer {

	public function render($module, $attribs = array(), $content = null) {
		if (!is_object($module)) {
			$title = isset($attribs['title']) ? $attribs['title'] : null;
            $module_id = isset($attribs['number']) ? $attribs['number'] : null;

			$module = MModuleHelper::getModule($module, $title, $module_id);

			if (!is_object($module)) {
				if (is_null($content)) {
					return '';
				}
				else {

					$tmp = $module;
					$module = new stdClass;
					$module->params = null;
					$module->module = $tmp;
					$module->id = 0;
					$module->user = 0;
				}
			}
		}

		// Get the user and configuration object
		// $user = MFactory::getUser();
		$conf = MFactory::getConfig();

		// Set the module content
		if (!is_null($content)) {
			$module->content = $content;
		}

		// Get module parameters
		$params = new MRegistry;
		$params->loadString($module->params);

		// Use parameters from template
		if (isset($attribs['params'])) {
			$template_params = new MRegistry;
			$template_params->loadString(html_entity_decode($attribs['params'], ENT_COMPAT, 'UTF-8'));
			$params->merge($template_params);
			$module = clone $module;
			$module->params = (string) $params;
		}

		$contents = MModuleHelper::renderModule($module, $attribs);

		return $contents;
	}
}