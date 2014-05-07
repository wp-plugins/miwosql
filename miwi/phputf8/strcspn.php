<?php
function utf8_strcspn($str, $mask, $start = NULL, $length = NULL) {

    if ( empty($mask) || strlen($mask) == 0 ) {
        return NULL;
    }

    $mask = preg_replace('!([\\\\\\-\\]\\[/^])!','\\\${1}',$mask);

    if ( $start !== NULL || $length !== NULL ) {
        $str = utf8_substr($str, $start, $length);
    }

    preg_match('/^[^'.$mask.']+/u',$str, $matches);

    if ( isset($matches[0]) ) {
        return utf8_strlen($matches[0]);
    }

    return 0;

}