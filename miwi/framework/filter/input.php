<?php
/*
* @package		Miwi Framework
* @copyright	Copyright (C) 2009-2014 Miwisoft, LLC. All rights reserved.
* @copyright	Copyright (C) 2005-2012 Open Source Matters, Inc. All rights reserved.
* @license		GNU General Public License version 2 or later
*/

defined('MIWI') or die('MIWI');

class MFilterInput extends MObject {

    protected static $instances = array();
    public $tagsArray;
    public $attrArray;
    public $tagsMethod;
    public $attrMethod;
    public $xssAuto;
    public $tagBlacklist = array(
        'applet',
        'body',
        'bgsound',
        'base',
        'basefont',
        'embed',
        'frame',
        'frameset',
        'head',
        'html',
        'id',
        'iframe',
        'ilayer',
        'layer',
        'link',
        'meta',
        'name',
        'object',
        'script',
        'style',
        'title',
        'xml'
    );

    public $attrBlacklist = array(
        'action',
        'background',
        'codebase',
        'dynsrc',
        'lowsrc'
    );

    public function __construct($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1) {
        // Make sure user defined arrays are in lowercase
        $tagsArray = array_map('strtolower', (array)$tagsArray);
        $attrArray = array_map('strtolower', (array)$attrArray);

        // Assign member variables
        $this->tagsArray = $tagsArray;
        $this->attrArray = $attrArray;
        $this->tagsMethod = $tagsMethod;
        $this->attrMethod = $attrMethod;
        $this->xssAuto = $xssAuto;
    }

    public static function &getInstance($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1) {
        $sig = md5(serialize(array($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto)));

        if (empty(self::$instances[$sig])) {
            self::$instances[$sig] = new MFilterInput($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto);
        }

        return self::$instances[$sig];
    }

    public function clean($source, $type = 'string') {
        // Handle the type constraint
        switch (strtoupper($type)) {
            case 'INT':
            case 'INTEGER':
                // Only use the first integer value
                preg_match('/-?[0-9]+/', (string)$source, $matches);
                $result = @ (int)$matches[0];
                break;

            case 'UINT':
                // Only use the first integer value
                preg_match('/-?[0-9]+/', (string)$source, $matches);
                $result = @ abs((int)$matches[0]);
                break;

            case 'FLOAT':
            case 'DOUBLE':
                // Only use the first floating point value
                preg_match('/-?[0-9]+(\.[0-9]+)?/', (string)$source, $matches);
                $result = @ (float)$matches[0];
                break;

            case 'BOOL':
            case 'BOOLEAN':
                $result = (bool)$source;
                break;

            case 'WORD':
                $result = (string)preg_replace('/[^A-Z_]/i', '', $source);
                break;

            case 'ALNUM':
                $result = (string)preg_replace('/[^A-Z0-9]/i', '', $source);
                break;

            case 'CMD':
                $result = (string)preg_replace('/[^A-Z0-9_\.-]/i', '', $source);
                $result = ltrim($result, '.');
                break;

            case 'BASE64':
                $result = (string)preg_replace('/[^A-Z0-9\/+=]/i', '', $source);
                break;

            case 'STRING':
                $result = (string)$this->_remove($this->_decode((string)$source));
                break;

            case 'HTML':
                $result = (string)$this->_remove((string)$source);
                break;

            case 'ARRAY':
                $result = (array)$source;
                break;

            case 'PATH':
                $pattern = '/^[A-Za-z0-9_-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
                preg_match($pattern, (string)$source, $matches);
                $result = @ (string)$matches[0];
                break;

            case 'USERNAME':
                $result = (string)preg_replace('/[\x00-\x1F\x7F<>"\'%&]/', '', $source);
                break;

            default:
                // Are we dealing with an array?
                if (is_array($source)) {
                    foreach ($source as $key => $value) {
                        // filter element for XSS and other 'bad' code etc.
                        if (is_string($value)) {
                            $source[$key] = $this->_remove($this->_decode($value));
                        }
                    }
                    $result = $source;
                }
                else {
                    // Or a string?
                    if (is_string($source) && !empty($source)) {
                        // filter source for XSS and other 'bad' code etc.
                        $result = $this->_remove($this->_decode($source));
                    }
                    else {
                        // Not an array or string.. return the passed parameter
                        $result = $source;
                    }
                }
                break;
        }

        return $result;
    }

    public static function checkAttribute($attrSubSet) {
        $attrSubSet[0] = strtolower($attrSubSet[0]);
        $attrSubSet[1] = strtolower($attrSubSet[1]);

        return (((strpos($attrSubSet[1], 'expression') !== false) && ($attrSubSet[0]) == 'style') || (strpos($attrSubSet[1], 'javascript:') !== false) ||
            (strpos($attrSubSet[1], 'behaviour:') !== false) || (strpos($attrSubSet[1], 'vbscript:') !== false) ||
            (strpos($attrSubSet[1], 'mocha:') !== false) || (strpos($attrSubSet[1], 'livescript:') !== false));
    }

    protected function _remove($source) {
        $loopCounter = 0;

        // Iteration provides nested tag protection
        while ($source != $this->_cleanTags($source)) {
            $source = $this->_cleanTags($source);
            $loopCounter++;
        }

        return $source;
    }

    protected function _cleanTags($source) {
        // First, pre-process this for illegal characters inside attribute values
        $source = $this->_escapeAttributeValues($source);
        // In the beginning we don't really have a tag, so everything is postTag
        $preTag = null;
        $postTag = $source;
        $currentSpace = false;
        // Setting to null to deal with undefined variables
        $attr = '';

        // Is there a tag? If so it will certainly start with a '<'.
        $tagOpen_start = strpos($source, '<');

        while ($tagOpen_start !== false) {
            // Get some information about the tag we are processing
            $preTag .= substr($postTag, 0, $tagOpen_start);
            $postTag = substr($postTag, $tagOpen_start);
            $fromTagOpen = substr($postTag, 1);
            $tagOpen_end = strpos($fromTagOpen, '>');

            // Check for mal-formed tag where we have a second '<' before the first '>'
            $nextOpenTag = (strlen($postTag) > $tagOpen_start) ? strpos($postTag, '<', $tagOpen_start + 1) : false;
            if (($nextOpenTag !== false) && ($nextOpenTag < $tagOpen_end)) {
                // At this point we have a mal-formed tag -- remove the offending open
                $postTag = substr($postTag, 0, $tagOpen_start) . substr($postTag, $tagOpen_start + 1);
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }

            // Let's catch any non-terminated tags and skip over them
            if ($tagOpen_end === false) {
                $postTag = substr($postTag, $tagOpen_start + 1);
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }

            // Do we have a nested tag?
            $tagOpen_nested = strpos($fromTagOpen, '<');
            $tagOpen_nested_end = strpos(substr($postTag, $tagOpen_end), '>');
            if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
                $preTag .= substr($postTag, 0, ($tagOpen_nested + 1));
                $postTag = substr($postTag, ($tagOpen_nested + 1));
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }

            // Let's get some information about our tag and setup attribute pairs
            $tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
            $currentTag = substr($fromTagOpen, 0, $tagOpen_end);
            $tagLength = strlen($currentTag);
            $tagLeft = $currentTag;
            $attrSet = array();
            $currentSpace = strpos($tagLeft, ' ');

            // Are we an open tag or a close tag?
            if (substr($currentTag, 0, 1) == '/') {
                // Close Tag
                $isCloseTag = true;
                list ($tagName) = explode(' ', $currentTag);
                $tagName = substr($tagName, 1);
            }
            else {
                // Open Tag
                $isCloseTag = false;
                list ($tagName) = explode(' ', $currentTag);
            }

            if ((!preg_match("/^[a-z][a-z0-9]*$/i", $tagName)) || (!$tagName) || ((in_array(strtolower($tagName), $this->tagBlacklist)) && ($this->xssAuto))) {
                $postTag = substr($postTag, ($tagLength + 2));
                $tagOpen_start = strpos($postTag, '<');
                // Strip tag
                continue;
            }

            while ($currentSpace !== false) {
                $attr = '';
                $fromSpace = substr($tagLeft, ($currentSpace + 1));
                $nextEqual = strpos($fromSpace, '=');
                $nextSpace = strpos($fromSpace, ' ');
                $openQuotes = strpos($fromSpace, '"');
                $closeQuotes = strpos(substr($fromSpace, ($openQuotes + 1)), '"') + $openQuotes + 1;

                $startAtt = '';
                $startAttPosition = 0;

                // Find position of equal and open quotes ignoring
                if (preg_match('#\s*=\s*\"#', $fromSpace, $matches, PREG_OFFSET_CAPTURE)) {
                    $startAtt = $matches[0][0];
                    $startAttPosition = $matches[0][1];
                    $closeQuotes = strpos(substr($fromSpace, ($startAttPosition + strlen($startAtt))), '"') + $startAttPosition + strlen($startAtt);
                    $nextEqual = $startAttPosition + strpos($startAtt, '=');
                    $openQuotes = $startAttPosition + strpos($startAtt, '"');
                    $nextSpace = strpos(substr($fromSpace, $closeQuotes), ' ') + $closeQuotes;
                }

                // Do we have an attribute to process? [check for equal sign]
                if ($fromSpace != '/' && (($nextEqual && $nextSpace && $nextSpace < $nextEqual) || !$nextEqual)) {
                    if (!$nextEqual) {
                        $attribEnd = strpos($fromSpace, '/') - 1;
                    }
                    else {
                        $attribEnd = $nextSpace - 1;
                    }
                    // If there is an ending, use this, if not, do not worry.
                    if ($attribEnd > 0) {
                        $fromSpace = substr($fromSpace, $attribEnd + 1);
                    }
                }
                if (strpos($fromSpace, '=') !== false) {
                    // If the attribute value is wrapped in quotes we need to grab the substring from
                    // the closing quote, otherwise grab until the next space.
                    if (($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes + 1)), '"') !== false)) {
                        $attr = substr($fromSpace, 0, ($closeQuotes + 1));
                    }
                    else {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                }
                // No more equal signs so add any extra text in the tag into the attribute array [eg. checked]
                else {
                    if ($fromSpace != '/') {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                }

                // Last Attribute Pair
                if (!$attr && $fromSpace != '/') {
                    $attr = $fromSpace;
                }

                // Add attribute pair to the attribute array
                $attrSet[] = $attr;

                // Move search point and continue iteration
                $tagLeft = substr($fromSpace, strlen($attr));
                $currentSpace = strpos($tagLeft, ' ');
            }

            // Is our tag in the user input array?
            $tagFound = in_array(strtolower($tagName), $this->tagsArray);

            // If the tag is allowed let's append it to the output string.
            if ((!$tagFound && $this->tagsMethod) || ($tagFound && !$this->tagsMethod)) {
                // Reconstruct tag with allowed attributes
                if (!$isCloseTag) {
                    // Open or single tag
                    $attrSet = $this->_cleanAttributes($attrSet);
                    $preTag .= '<' . $tagName;
                    for ($i = 0, $count = count($attrSet); $i < $count; $i++) {
                        $preTag .= ' ' . $attrSet[$i];
                    }

                    // Reformat single tags to XHTML
                    if (strpos($fromTagOpen, '</' . $tagName)) {
                        $preTag .= '>';
                    }
                    else {
                        $preTag .= ' />';
                    }
                }
                // Closing tag
                else {
                    $preTag .= '</' . $tagName . '>';
                }
            }

            // Find next tag's start and continue iteration
            $postTag = substr($postTag, ($tagLength + 2));
            $tagOpen_start = strpos($postTag, '<');
        }

        // Append any code after the end of tags and return
        if ($postTag != '<') {
            $preTag .= $postTag;
        }

        return $preTag;
    }

    protected function _cleanAttributes($attrSet) {
        // Initialise variables.
        $newSet = array();

        $count = count($attrSet);
        // Iterate through attribute pairs
        for ($i = 0; $i < $count; $i++) {
            // Skip blank spaces
            if (!$attrSet[$i]) {
                continue;
            }

            // Split into name/value pairs
            $attrSubSet = explode('=', trim($attrSet[$i]), 2);
            // Take the last attribute in case there is an attribute with no value
            $attrSubSet_0 = explode(' ', trim($attrSubSet[0]));
            $attrSubSet[0] = array_pop($attrSubSet_0);

            // Remove all "non-regular" attribute names
            // AND blacklisted attributes

            if ((!preg_match('/[a-z]*$/i', $attrSubSet[0]))
                || (($this->xssAuto) && ((in_array(strtolower($attrSubSet[0]), $this->attrBlacklist))
                        || (substr($attrSubSet[0], 0, 2) == 'on')))
            ) {
                continue;
            }

            // XSS attribute value filtering
            if (isset($attrSubSet[1])) {
                // trim leading and trailing spaces
                $attrSubSet[1] = trim($attrSubSet[1]);
                // strips unicode, hex, etc
                $attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
                // Strip normal newline within attr value
                $attrSubSet[1] = preg_replace('/[\n\r]/', '', $attrSubSet[1]);
                // Strip double quotes
                $attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
                // Convert single quotes from either side to doubles (Single quotes shouldn't be used to pad attr values)
                if ((substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) == "'")) {
                    $attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
                }
                // Strip slashes
                $attrSubSet[1] = stripslashes($attrSubSet[1]);
            }
            else {
                continue;
            }

            // Autostrip script tags
            if (self::checkAttribute($attrSubSet)) {
                continue;
            }

            // Is our attribute in the user input array?
            $attrFound = in_array(strtolower($attrSubSet[0]), $this->attrArray);

            // If the tag is allowed lets keep it
            if ((!$attrFound && $this->attrMethod) || ($attrFound && !$this->attrMethod)) {
                // Does the attribute have a value?
                if (empty($attrSubSet[1]) === false) {
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
                }
                elseif ($attrSubSet[1] === "0") {
                    // Special Case
                    // Is the value 0?
                    $newSet[] = $attrSubSet[0] . '="0"';
                }
                else {
                    // Leave empty attributes alone
                    $newSet[] = $attrSubSet[0] . '=""';
                }
            }
        }

        return $newSet;
    }

    protected function _decode($source) {
        static $ttr;

        if (!is_array($ttr)) {
            // Entity decode
            if (version_compare(PHP_VERSION, '5.3.4', '>=')) {
                $trans_tbl = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT, 'ISO-8859-1');
            }
            else {
                $trans_tbl = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT);
            }
            foreach ($trans_tbl as $k => $v) {
                $ttr[$v] = utf8_encode($k);
            }
        }
        $source = strtr($source, $ttr);
        // Convert decimal
        $source = preg_replace_callback('/&#(\d+);/m', "callbackMFilterInputConvertDecimal", $source); // decimal notation
        // Convert hex
        $source = preg_replace_callback('/&#x([a-f0-9]+);/mi', "callbackMFilterInputConvertHex", $source); // hex notation

        return $source;
    }

    protected function _escapeAttributeValues($source) {
        $alreadyFiltered = '';
        $remainder = $source;
        $badChars = array('<', '"', '>');
        $escapedChars = array('&lt;', '&quot;', '&gt;');
        // Process each portion based on presence of =" and "<space>, "/>, or ">
        // See if there are any more attributes to process
        while (preg_match('#<[^>]*?=\s*?(\"|\')#s', $remainder, $matches, PREG_OFFSET_CAPTURE)) {
            // get the portion before the attribute value
            $quotePosition = $matches[0][1];
            $nextBefore = $quotePosition + strlen($matches[0][0]);

            // Figure out if we have a single or double quote and look for the matching closing quote
            // Closing quote should be "/>, ">, "<space>, or " at the end of the string
            $quote = substr($matches[0][0], -1);
            $pregMatch = ($quote == '"') ? '#(\"\s*/\s*>|\"\s*>|\"\s+|\"$)#' : "#(\'\s*/\s*>|\'\s*>|\'\s+|\'$)#";

            // get the portion after attribute value
            if (preg_match($pregMatch, substr($remainder, $nextBefore), $matches, PREG_OFFSET_CAPTURE)) {
                // We have a closing quote
                $nextAfter = $nextBefore + $matches[0][1];
            }
            else {
                // No closing quote
                $nextAfter = strlen($remainder);
            }
            // Get the actual attribute value
            $attributeValue = substr($remainder, $nextBefore, $nextAfter - $nextBefore);
            // Escape bad chars
            $attributeValue = str_replace($badChars, $escapedChars, $attributeValue);
            $attributeValue = $this->_stripCSSExpressions($attributeValue);
            $alreadyFiltered .= substr($remainder, 0, $nextBefore) . $attributeValue . $quote;
            $remainder = substr($remainder, $nextAfter + 1);
        }

        // At this point, we just have to return the $alreadyFiltered and the $remainder
        return $alreadyFiltered . $remainder;
    }

    protected function _stripCSSExpressions($source) {
        // Strip any comments out (in the form of /*...*/)
        $test = preg_replace('#\/\*.*\*\/#U', '', $source);
        // Test for :expression
        if (!stripos($test, ':expression')) {
            // Not found, so we are done
            $return = $source;
        }
        else {
            // At this point, we have stripped out the comments and have found :expression
            // Test stripped string for :expression followed by a '('
            if (preg_match_all('#:expression\s*\(#', $test, $matches)) {
                // If found, remove :expression
                $test = str_ireplace(':expression', '', $test);
                $return = $test;
            }
        }
        return $return;
    }
}

if (!function_exists('callbackMFilterInputConvertDecimal')) {

    function callbackMFilterInputConvertDecimal($matches)
    {
        return utf8_encode(chr($matches[1]));
    }
}

if (!function_exists('callbackMFilterInputConvertHex')) {

    function callbackMFilterInputConvertHex($matches)
    {
        return utf8_encode(chr('0x' . $matches[1]));
    }
}
