<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

mimport('framework.widget.widget');
mimport('framework.application.module.helper');

class MWidgetHelper extends MObject {

	public static function startWidgets($option) {
        $modules = MModuleHelper::getModules();

        foreach ($modules as $module) {
			if (!is_object($module)) {
				continue;
			}
		
            if (!strpos($module->module, $option)) {
                continue;
            }

            $class_name = $module->module.'_widget';
			
			if (!defined($class_name)) {
				define($class_name, $class_name);
			}

            eval("class $class_name extends MWidget {};");

            add_action('widgets_init', create_function('', 'return register_widget('.$class_name.');'));
        }
	}
}