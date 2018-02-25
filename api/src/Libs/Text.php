<?php
namespace QTool\Api\Libs;

class Text {

    public static function camelize($text, $dash = '-', $capitalFirst = TRUE) {
        $text = str_replace($dash, '', ucwords($text, $dash));

        if ( ! $capitalFirst) {
            $text = lcfirst($text);
        }
        return $text;
    }

    public static function uncamelize($text, $dash = '-') {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $text, $matches);
        
        $result = $matches[0];
        
        foreach($result as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode($dash, $result);
    }
}