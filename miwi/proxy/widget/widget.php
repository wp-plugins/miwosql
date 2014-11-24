<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MWidget extends WP_Widget {

	public function __construct() {
        $this->module = str_replace('_widget', '', $this->class_name);

        $xml_file = MPATH_MIWI.'/modules/'.$this->module.'/'.$this->module.'.xml';
        $xml = simplexml_load_file($xml_file, 'SimpleXMLElement');

        if (is_null($xml) || !($xml instanceof SimpleXMLElement)) {
            $widget_name = str_replace('mod_', '', $this->module);
            $widget_name = str_replace('_', ' ', $widget_name);
            $widget_name = ucwords($widget_name);
            $widget_desc = 'Displays '.$widget_name;
        }
        else {
            $widget_name = $xml->name;
            $widget_desc = str_replace('module', 'widget', $xml->description);
        }
		
		$widget_ops = array('classname' => $widget_name, 'description' => $widget_desc);
		$this->WP_Widget($this->class_name, $widget_name, $widget_ops);
	}

	public function form($instance) {
		MFactory::getLanguage()->load($this->module, MPATH_ADMINISTRATOR);
		
        $instance = MFactory::getWOption('widget_'.$this->module.'_widget', false, $this->number);
        $instance = wp_parse_args((array) $instance, array('title' => '' ));

        $title = $instance['title'];
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <?php

        $form = MForm::getInstance($this->module.'_form', MPATH_MIWI.'/modules/'.$this->module.'/'.$this->module.'.xml', array(), false, 'config');
        $form_values = array('params' => $instance);
        $form->bind($form_values);

        $field_sets = $form->getFieldsets();
        foreach ($field_sets as $name => $field_set) {
            if ($field_set->name != 'basic') {
                continue;
            }

            foreach ($form->getFieldset($name) as $field) {
                $field_name = $field->get('fieldname');

                if ($field_name == 'moduleclass_sfx') {
                    continue;
                }

                $field->set('id', $this->get_field_id($field_name));
                $field->set('name', $this->get_field_name($field_name));
                ?>
                <p>
                    <?php if (!$field->hidden) { ?>
                    <label for="<?php echo $this->get_field_id('title'); ?>"><?php echo $field->label; ?></label>
                    <?php } ?>
                    <?php echo $field->input; ?>
                </p>
            <?php
            }
        }
	}

	public function update($new_instance, $old_instance) {
		$instance = $old_instance;

		$instance['title'] = $new_instance['title'];

        $form = MForm::getInstance($this->module.'_form', MPATH_MIWI.'/modules/'.$this->module.'/'.$this->module.'.xml', array(), false, 'config');

        $field_sets = $form->getFieldsets();
        foreach ($field_sets as $name => $field_set) {
            if ($field_set->name != 'basic') {
                continue;
            }

            foreach ($form->getFieldset($name) as $field) {
                $field_name = $field->get('fieldname');

                if ($field_name == 'moduleclass_sfx') {
                    continue;
                }

                $instance[$field_name] = $new_instance[$field_name];
            }
        }

		return $instance;
	}

	public function widget($args, $instance) {
		MFactory::getLanguage()->load($this->module, MPATH_SITE);
		
		extract($args, EXTR_SKIP);

		echo $before_widget;

		$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);

		if (!empty($title)) {
			echo $before_title . $title . $after_title;;
		}
		
		echo '<div>';
		
		$attribs = array('style' => 'xhtml', 'number' => $this->number);
		
		echo MFactory::getDocument()->loadRenderer('module')->render($this->module, $attribs);
		
		echo '</div>';

		echo $after_widget;
	}
}