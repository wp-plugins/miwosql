<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFormFieldPassword extends MFormField {

    protected $type = 'Password';

    protected function getInput() {
        // Initialize some field attributes.
        $size      = $this->element['size'] ? ' size="' . (int)$this->element['size'] . '"' : '';
        $maxLength = $this->element['maxlength'] ? ' maxlength="' . (int)$this->element['maxlength'] . '"' : '';
        $class     = $this->element['class'] ? ' class="' . (string)$this->element['class'] . '"' : '';
        $auto      = ((string)$this->element['autocomplete'] == 'off') ? ' autocomplete="off"' : '';
        $readonly  = ((string)$this->element['readonly'] == 'true') ? ' readonly="readonly"' : '';
        $disabled  = ((string)$this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
        $meter     = ((string)$this->element['strengthmeter'] == 'true');
        $threshold = $this->element['threshold'] ? (int)$this->element['threshold'] : 66;

        $script = '';
        if ($meter) {
            MHtml::_('script', 'system/passwordstrength.js', true, true);
            $script = '<script type="text/javascript">new Form.PasswordStrength("' . $this->id . '",
				{
					threshold: ' . $threshold . ',
					onUpdate: function(element, strength, threshold) {
						element.set("data-passwordstrength", strength);
					}
				}
			);</script>';
        }

        return '<input type="password" name="' . $this->name . '" id="' . $this->id . '"' .
        ' value="' . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' .
        $auto . $class . $readonly . $disabled . $size . $maxLength . '/>' . $script;
    }
}