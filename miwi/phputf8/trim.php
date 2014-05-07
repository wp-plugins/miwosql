<?php

function utf8_ltrim( $str, $charlist = FALSE ) {
    if($charlist === FALSE) return ltrim($str);

    //quote charlist for use in a characterclass
    $charlist = preg_replace('!([\\\\\\-\\]\\[/^])!','\\\${1}',$charlist);

    return preg_replace('/^['.$charlist.']+/u','',$str);
}

function utf8_rtrim( $str, $charlist = FALSE ) {
    if($charlist === FALSE) return rtrim($str);

    //quote charlist for use in a characterclass
    $charlist = preg_replace('!([\\\\\\-\\]\\[/^])!','\\\${1}',$charlist);

    return preg_replace('/['.$charlist.']+$/u','',$str);
}

function utf8_trim( $str, $charlist = FALSE ) {
    if($charlist === FALSE) return trim($str);
    return utf8_ltrim(utf8_rtrim($str, $charlist), $charlist);
}