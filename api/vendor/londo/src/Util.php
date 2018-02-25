<?php
namespace Londo;

class Util {

    public static function fixUTF8($data) {
        if (is_string($data)) {
            $regex = '/((?: [\x00-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3}){1,100})|./x';
            $data = preg_replace($regex, '$1', $data);
        } else if (is_array($data)) {
            foreach($data as $key => $val) {
                $data[$key] = self::fixUTF8($val);
            }
        } else if (is_object($data)) {
            foreach($data as $key => $val) {
                $data->{$key} = self::fixUTF8($val);
            }
        }
        return $data;
    }

    public static function responseJson($data) {
        header('Content-Type: application/json');
        $data = self::fixUTF8($data);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

}