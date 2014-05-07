<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

abstract class MHtmlEmail {

    public static function cloak($mail, $mailto = true, $text = '', $email = true) {
        // Convert text
        $mail = self::_convertEncoding($mail);

        // Split email by @ symbol
        $mail       = explode('@', $mail);
        $mail_parts = explode('.', $mail[1]);

        // Random number
        $rand = rand(1, 100000);

        $replacement = "<script type='text/javascript'>";
        $replacement .= "\n <!--";
        $replacement .= "\n var prefix = '&#109;a' + 'i&#108;' + '&#116;o';";
        $replacement .= "\n var path = 'hr' + 'ef' + '=';";
        $replacement .= "\n var addy" . $rand . " = '" . @$mail[0] . "' + '&#64;';";
        $replacement .= "\n addy" . $rand . " = addy" . $rand . " + '" . implode("' + '&#46;' + '", $mail_parts) . "';";

        if ($mailto) {
            // Special handling when mail text is different from mail address
            if ($text) {
                if ($email) {
                    // Convert text
                    $text = self::_convertEncoding($text);

                    // Split email by @ symbol
                    $text       = explode('@', $text);
                    $text_parts = explode('.', $text[1]);
                    $replacement .= "\n var addy_text" . $rand . " = '" . @$text[0] . "' + '&#64;' + '" . implode("' + '&#46;' + '", @$text_parts)
                            . "';";
                }
                else {
                    $replacement .= "\n var addy_text" . $rand . " = '" . $text . "';";
                }
                $replacement .= "\n document.write('<a ' + path + '\'' + prefix + ':' + addy" . $rand . " + '\'>');";
                $replacement .= "\n document.write(addy_text" . $rand . ");";
                $replacement .= "\n document.write('<\/a>');";
            }
            else {
                $replacement .= "\n document.write('<a ' + path + '\'' + prefix + ':' + addy" . $rand . " + '\'>');";
                $replacement .= "\n document.write(addy" . $rand . ");";
                $replacement .= "\n document.write('<\/a>');";
            }
        }
        else {
            $replacement .= "\n document.write(addy" . $rand . ");";
        }
        $replacement .= "\n //-->";
        $replacement .= '\n </script>';

        // XHTML compliance no Javascript text handling
        $replacement .= "<script type='text/javascript'>";
        $replacement .= "\n <!--";
        $replacement .= "\n document.write('<span style=\'display: none;\'>');";
        $replacement .= "\n //-->";
        $replacement .= "\n </script>";
        $replacement .= MText::_('MLIB_HTML_CLOAKING');
        $replacement .= "\n <script type='text/javascript'>";
        $replacement .= "\n <!--";
        $replacement .= "\n document.write('</');";
        $replacement .= "\n document.write('span>');";
        $replacement .= "\n //-->";
        $replacement .= "\n </script>";

        return $replacement;
    }

    protected static function _convertEncoding($text) {
        // Replace vowels with character encoding
        $text = str_replace('a', '&#97;', $text);
        $text = str_replace('e', '&#101;', $text);
        $text = str_replace('i', '&#105;', $text);
        $text = str_replace('o', '&#111;', $text);
        $text = str_replace('u', '&#117;', $text);

        return $text;
    }
}