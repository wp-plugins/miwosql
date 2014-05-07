<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlContent {

    public static function prepare($text, $params = null, $context = 'text') {
        if ($params === null) {
            $params = new MObject;
        }
        $article       = new stdClass;
        $article->text = $text;
        MPluginHelper::importPlugin('content');
        $dispatcher = MDispatcher::getInstance();
        $dispatcher->trigger('onContentPrepare', array($context, &$article, &$params, 0));

        return $article->text;
    }
}