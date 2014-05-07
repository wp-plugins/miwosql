<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MHtmlString {

    public static function truncate($text, $length = 0, $noSplit = true, $allowHtml = true) {
        // Check if HTML tags are allowed.
        if (!$allowHtml) {
            // Deal with spacing issues in the input.
            $text = str_replace('>', '> ', $text);
            $text = str_replace(array('&nbsp;', '&#160;'), ' ', $text);
            $text = MString::trim(preg_replace('#\s+#mui', ' ', $text));

            // Strip the tags from the input and decode entities.
            $text = strip_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

            // Remove remaining extra spaces.
            $text = str_replace('&nbsp;', ' ', $text);
            $text = MString::trim(preg_replace('#\s+#mui', ' ', $text));
        }

        // Truncate the item text if it is too long.
        if ($length > 0 && MString::strlen($text) > $length) {
            // Find the first space within the allowed length.
            $tmp = MString::substr($text, 0, $length);

            if ($noSplit) {
                $offset = MString::strrpos($tmp, ' ');
                if (MString::strrpos($tmp, '<') > MString::strrpos($tmp, '>')) {
                    $offset = MString::strrpos($tmp, '<');
                }
                $tmp = MString::substr($tmp, 0, $offset);

                // If we don't have 3 characters of room, go to the second space within the limit.
                if (MString::strlen($tmp) > $length - 3) {
                    $tmp = MString::substr($tmp, 0, MString::strrpos($tmp, ' '));
                }
            }

            if ($allowHtml) {
                // Put all opened tags into an array
                preg_match_all("#<([a-z][a-z0-9]*)\b.*?(?!/)>#i", $tmp, $result);
                $openedTags = $result[1];
                $openedTags = array_diff($openedTags, array("img", "hr", "br"));
                $openedTags = array_values($openedTags);

                // Put all closed tags into an array
                preg_match_all("#</([a-z]+)>#iU", $tmp, $result);
                $closedTags = $result[1];

                $numOpened = count($openedTags);

                // All tags are closed
                if (count($closedTags) == $numOpened) {
                    return $tmp . '...';
                }

                $openedTags = array_reverse($openedTags);

                // Close tags
                for ($i = 0; $i < $numOpened; $i++) {
                    if (!in_array($openedTags[$i], $closedTags)) {
                        $tmp .= "</" . $openedTags[$i] . ">";
                    }
                    else {
                        unset($closedTags[array_search($openedTags[$i], $closedTags)]);
                    }
                }
            }

            $text = $tmp . '...';
        }

        return $text;
    }

    public static function abridge($text, $length = 50, $intro = 30) {
        // Abridge the item text if it is too long.
        if (MString::strlen($text) > $length) {
            // Determine the remaining text length.
            $remainder = $length - ($intro + 3);

            // Extract the beginning and ending text sections.
            $beg = MString::substr($text, 0, $intro);
            $end = MString::substr($text, MString::strlen($text) - $remainder);

            // Build the resulting string.
            $text = $beg . '...' . $end;
        }

        return $text;
    }
}