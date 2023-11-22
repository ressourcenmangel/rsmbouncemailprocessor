<?php

namespace RSM\Rsmbouncemailprocessor\Utility;

/**
 * This library is a wrapper around the Imap library functions included in php.
 *
 * @package Rsmbouncemailprocessor
 * @author  Robert Hafner <tedivm@tedivm.com>
 * @author  Sergey Linnik <linniksa@gmail.com>
 * @author  Ralph Brugger <ralph.brugger@ressourcenmangel.de>
 */
final class MIME
{
    /**
     * @param string|null $text
     * @param string $targetCharset
     *
     * @return string|null
     */
    public static function decode(string|null $text = null, string$targetCharset = 'UTF-8'):string|null
    {
        $result = '';
        if (null === $text) {
            return null;
        }

        foreach (imap_mime_header_decode($text) as $word) {
            $ch = 'default' === $word->charset ? 'ascii' : $word->charset;
            if ($ch==="ascii" && ($c=strtoupper(mb_detect_encoding($word->text)))!==strtoupper($ch)) {
                $ch=$c;
            }
            $result .= @iconv($ch, $targetCharset, $word->text);
        }

        return $result;
    }
}
