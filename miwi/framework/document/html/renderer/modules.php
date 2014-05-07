<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MDocumentRendererModules extends MDocumentRenderer {

    public function render($position, $params = array(), $content = null) {
        $renderer = $this->_doc->loadRenderer('module');
        $buffer = '';

        foreach (MModuleHelper::getModules($position) as $mod) {
            $buffer .= $renderer->render($mod, $params, $content);
        }
        return $buffer;
    }
}
