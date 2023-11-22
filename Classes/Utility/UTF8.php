<?php
declare(strict_types=1);

/**
 * This library is a wrapper around the Imap library functions included in php.
 *
 * @package Rsmbouncemailprocessor
 * @author  Ralph Brugger <ralph.brugger@ressourcenmangel.de>
 */
namespace RSM\Rsmbouncemailprocessor\Utility;

use function function_exists;

final class UTF8
{
    public static function fix($text)
    {
        if (!is_string($text) || !$text) {
            return $text;
        }

        if (function_exists('mb_convert_encoding')) {
            if ($val = @mb_convert_encoding($text, 'utf-8', 'utf-8')) {
                return $val;
            }
        }

        $buf = '';
        for ($i = 0, $max = strlen($text); $i < $max; $i++) {
            $c1 = $text[$i];

            if ($c1 <= "\x7F") { // single byte
                $buf .= $c1;
            } elseif ($c1 <= "\xC1") { // single byte (invalid)
                $buf .= '?';
            } elseif ($c1 <= "\xDF") { // 2 byte
                $c2 = $i + 1 >= $max ? "\x00" : $text[$i + 1];

                if ($c2 >= "\x80" && $c2 <= "\xBF") {
                    $buf .= $c1 . $c2;
                    $i   += 1;
                } else {
                    $buf .= '?';
                }
            } elseif ($c1 <= "\xEF") { // 3 bytes
                $c2 = $i + 1 >= $max ? "\x00" : $text[$i + 1];
                $c3 = $i + 2 >= $max ? "\x00" : $text[$i + 2];

                if ($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf") {
                    $buf .= $c1 . $c2 . $c3;
                    $i   += 2;
                } else {
                    $buf .= '?';
                }
            } else if ($c1 <= "\xF4") { //Should be converted to UTF8, if it's not UTF8 already
                $c2 = $i + 1 >= $max ? "\x00" : $text[$i + 1];
                $c3 = $i + 2 >= $max ? "\x00" : $text[$i + 2];
                $c4 = $i + 3 >= $max ? "\x00" : $text[$i + 3];

                if ($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf") {
                    $buf .= $c1 . $c2 . $c3 . $c4;
                    $i   += 3;
                } else {
                    $buf .= '?';
                }
            } else { // invalid
                $buf .= '?';
            }
        }

        return $buf;
    }
}
