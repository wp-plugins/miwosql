<?php
function utf8_stristr($str, $search) {

    if ( strlen($search) == 0 ) {
        return $str;
    }

    $lstr = utf8_strtolower($str);
    $lsearch = utf8_strtolower($search);
    preg_match('/^(.*)'.preg_quote($lsearch, '/').'/Us',$lstr, $matches);

    if ( count($matches) == 2 ) {
        return substr($str, strlen($matches[1]));
    }

    return FALSE;
}