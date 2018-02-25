<?php
namespace QTool\Api\Libs;

class Response {

    protected $_output;
    protected $_result;

    public function output($output) {
        $this->_output = (string)$output;
    }

    public function result($result) {
        $this->_result = $result;
    }

    public function send() {
        $output = $this->_output;
        $result = $this->_result;

        if ( ! is_null($result)) {

            if (is_array($result) || is_object($result)) {
                header('Content-Type: application/json');
                $output .= json_encode(UTF8::fix($result), JSON_PRETTY_PRINT);
            }
        }

        echo $output;
    }

    public function send304() {
        header('HTTP/1.1 304 Not Modified');
        exit();
    }

    public function send404() {
        header('HTTP/1.1 404 Not Found');
        exit();
    }

    public function send415() {
        header('HTTP/1.1 415 Unsupported Media Type');
        exit();
    }

}