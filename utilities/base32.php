<?php
namespace Wovax\IDX\Utilities;
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

class Base32 {
    static private $map = array(
        '0', '1', '2', '3', '4', '5', '6', '7',
        '8', '9', 'a', 'b', 'c', 'd', 'e', 'f',
        'g', 'h', 'j', 'k', 'm', 'n', 'p', 'q',
        'r', 's', 't', 'u', 'v', 'w', 'x', 'y'
    );
    static public function encodeString($str) {
        $arr = array_values(unpack('C*', $str));
        return self::encodeByteArray($arr);
    }
    static public function encodeByteArray($byte_arr) {
        if(!is_array($byte_arr)) {
            return '';
        }
        $length = count($byte_arr);
        $bits   = 8;
        $index  = 0;
        $prev   = 0;
        $str    = '';
        while($index < $length) {
            $bits -= 5;
            $val   =  $prev << (8 - $bits) | ($byte_arr[$index] >> $bits);
            $str  .= self::$map[0x1F & $val];
            if($bits < 5) {
                $prev = $byte_arr[$index];
                $bits += 8;
                $index++;
            }
        }
        if($bits > 0 && $bits != 8) {
            $bits -= 8;
            $str  .= self::$map[0x1F & ($prev << ( 5 - $bits))];
        }
        return $str;
    }
    static public function decodeToString($str) {
        $arr = self::decodeToArray($str);
        $str = '';
        foreach($arr as $val) {
            $str .= chr($val);
        }
        return $str;
    }
    static public function decodeToArray($str) {
        $map  = array_flip(self::$map); // char => int val
        $str  = strtolower($str);// Only work with lower case
        $str  = preg_replace('/[^0-9a-v]/', '', $str); // Remove anything not in the alphabet
        $len  = strlen($str);
        $data = array();
        $bits = 0;
        $val  = 0;
        for($i = 0; $i < $len; $i++) {
            $val = ( $val << 5 ) | $map[$str[$i]];
            $bits += 5;
            if($bits >= 8) {
                $bits  -= 8;
                $data[] = 0xFF & ($val >> $bits);
            }
        }
        return $data;
    }
    static public function getRandomID($bytes = 10) {
        // 10 bytes 2^80
        if($bytes < 1 || $bytes > 32) {
            $bytes = 10;
        }
        $byte_str = '';
        for($i = 0; $i < $bytes; $i++) {
            $byte_str .= chr(mt_rand(0, 255));
        }
        // just generate a psuedo random id for classes/ids so if multiple
        // items exist they likely are unique.
        return self::encodeString($byte_str);
    } 
}