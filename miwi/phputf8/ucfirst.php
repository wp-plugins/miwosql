<?php

function utf8_ucfirst($str){
    switch ( utf8_strlen($str) ) {
        case 0:
            return '';
        break;
        case 1:
            return utf8_strtoupper($str);
        break;
        default:
            preg_match('/^(.{1})(.*)$/us', $str, $matches);
            return utf8_strtoupper($matches[1]).$matches[2];
        break;
    }
}

