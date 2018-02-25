<?php
namespace QTool\Api\Libs;

class UTF8 {

    public static function fix($data) {
        if (is_string($data)) {
            $regex = '/((?: [\x00-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3}){1,100})|./x';
            $data = preg_replace($regex, '$1', $data);
        } else if (is_array($data)) {
            foreach($data as $key => $val) {
                $data[$key] = self::fix($val);
            }
        } else if (is_object($data)) {
            foreach($data as $key => $val) {
                $data->{$key} = self::fix($val);
            }
        }
        return $data;
    }

}