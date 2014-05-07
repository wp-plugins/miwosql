<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlImage {

    public static function site($file, $folder = '/images/system/', $altFile = null, $altFolder = '/images/system/', $alt = null, $attribs = null,
                                $asTag = true) {
        // Deprecation warning.
        MLog::add('MImage::site is deprecated.', MLog::WARNING, 'deprecated');

        static $paths;
        $app = MFactory::getApplication();

        if (!$paths) {
            $paths = array();
        }

        if (is_array($attribs)) {
            $attribs = MArrayHelper::toString($attribs);
        }

        $cur_template = $app->getTemplate();

        // Strip HTML.
        $alt = html_entity_decode($alt, ENT_COMPAT, 'UTF-8');

        if ($altFile) {
            $src = $altFolder . $altFile;
        }
        elseif ($altFile == -1) {
            return '';
        }
        else {
            $path = MPATH_SITE . '/templates/' . $cur_template . '/images/' . $file;
            if (!isset($paths[$path])) {
                if (file_exists(MPATH_SITE . '/templates/' . $cur_template . '/images/' . $file)) {
                    $paths[$path] = 'templates/' . $cur_template . '/images/' . $file;
                }
                else {
                    // Outputs only path to image.
                    $paths[$path] = $folder . $file;
                }
            }
            $src = $paths[$path];
        }

        if (substr($src, 0, 1) == "/") {
            $src = substr_replace($src, '', 0, 1);
        }

        // Prepend the base path.
        $src = MURI::base(true) . '/' . $src;

        // Outputs actual HTML <img> tag.
        if ($asTag) {
            return '<img src="' . $src . '" alt="' . $alt . '" ' . $attribs . ' />';
        }

        return $src;
    }

    public static function administrator($file, $folder = '/images/', $altFile = null, $altFolder = '/images/', $alt = null, $attribs = null,
                                         $asTag = true) {
        // Deprecation warning.
        MLog::add('MImage::administrator is deprecated.', MLog::WARNING, 'deprecated');

        $app = MFactory::getApplication();

        if (is_array($attribs)) {
            $attribs = MArrayHelper::toString($attribs);
        }

        $cur_template = $app->getTemplate();

        // Strip HTML.
        $alt = html_entity_decode($alt, ENT_COMPAT, 'UTF-8');

        if ($altFile) {
            $image = $altFolder . $altFile;
        }
        elseif ($altFile == -1) {
            $image = '';
        }
        else {
            if (file_exists(MPATH_ADMINISTRATOR . '/templates/' . $cur_template . '/images/' . $file)) {
                $image = 'templates/' . $cur_template . '/images/' . $file;
            }
            else {
                // Compatibility with previous versions.
                if (substr($folder, 0, 14) == "/administrator") {
                    $image = substr($folder, 15) . $file;
                }
                else {
                    $image = $folder . $file;
                }
            }
        }

        if (substr($image, 0, 1) == "/") {
            $image = substr_replace($image, '', 0, 1);
        }

        // Prepend the base path.
        $image = MURI::base(true) . '/' . $image;

        // Outputs actual HTML <img> tag.
        if ($asTag) {
            $image = '<img src="' . $image . '" alt="' . $alt . '" ' . $attribs . ' />';
        }

        return $image;
    }
}
