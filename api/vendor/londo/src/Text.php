<?php
namespace Londo;

class Text {

    public static function uncamelize($text, $separator = '-') {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $text, $matches);
        
        $result = $matches[0];
        
        foreach($result as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode($separator, $result);
    }
}