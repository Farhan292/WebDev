<?php

namespace App\Service;

class TextMods
{
    public function highlightImportant($string)
    {
        $modifiedString = str_ireplace('important', 'IMPORTANT', $string);
        return $modifiedString;
    }
}