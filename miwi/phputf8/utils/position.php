<?php

function utf8_byte_position() {

    $args = func_get_args();
    $str =& array_shift($args);
    if (!is_string($str)) return false;

    $result = array();

    // trivial byte index, character offset pair
    $prev = array(0,0);

    // use a short piece of str to estimate bytes per character
    // $i (& $j) -> byte indexes into $str
    $i = utf8_locate_next_chr($str, 300);

    // $c -> character offset into $str
    $c = strlen(utf8_decode(substr($str,0,$i)));

    // deal with arguments from lowest to highest
    sort($args);

    foreach ($args as $offset) {
        // sanity checks FIXME

        // 0 is an easy check
        if ($offset == 0) { $result[] = 0; continue; }

        // ensure no endless looping
        $safety_valve = 50;

        do {

            if ( ($c - $prev[1]) == 0 ) {
                // Hack: gone past end of string
                $error = 0;
                $i = strlen($str);
                break;
            }

            $j = $i + (int)(($offset-$c) * ($i - $prev[0]) / ($c - $prev[1]));

            // correct to utf8 character boundary
            $j = utf8_locate_next_chr($str, $j);

            // save the index, offset for use next iteration
            $prev = array($i,$c);

            if ($j > $i) {
                // determine new character offset
                $c += strlen(utf8_decode(substr($str,$i,$j-$i)));
            } else {
                // ditto
                $c -= strlen(utf8_decode(substr($str,$j,$i-$j)));
            }

            $error = abs($c-$offset);

            // ready for next time around
            $i = $j;

        // from 7 it is faster to iterate over the string
        } while ( ($error > 7) && --$safety_valve) ;

        if ($error && $error <= 7) {

            if ($c < $offset) {
                // move up
                while ($error--) { $i = utf8_locate_next_chr($str,++$i); }
            } else {
                // move down
                while ($error--) { $i = utf8_locate_current_chr($str,--$i); }
            }

            // ready for next arg
            $c = $offset;
        }
        $result[] = $i;
    }

    if ( count($result) == 1 ) {
        return $result[0];
    }

    return $result;
}

function utf8_locate_current_chr( &$str, $idx ) {

    if ($idx <= 0) return 0;

    $limit = strlen($str);
    if ($idx >= $limit) return $limit;

    // Binary value for any byte after the first in a multi-byte UTF-8 character
    // will be like 10xxxxxx so & 0xC0 can be used to detect this kind
    // of byte - assuming well formed UTF-8
    while ($idx && ((ord($str[$idx]) & 0xC0) == 0x80)) $idx--;

    return $idx;
}

function utf8_locate_next_chr( &$str, $idx ) {

    if ($idx <= 0) return 0;

    $limit = strlen($str);
    if ($idx >= $limit) return $limit;

    // Binary value for any byte after the first in a multi-byte UTF-8 character
    // will be like 10xxxxxx so & 0xC0 can be used to detect this kind
    // of byte - assuming well formed UTF-8
    while (($idx < $limit) && ((ord($str[$idx]) & 0xC0) == 0x80)) $idx++;

    return $idx;
}

