<?php
namespace QTool\Api\Libs;

class Request {

    protected $_method;
    protected $_query;
    protected $_post;
    protected $_body;
    protected $_headers;

    public function __construct() {
        $this->_query = $_GET;

        unset($this->_query['__route']);

        $this->_post = $_POST;
        $this->_body = file_get_contents('php://input');

        $this->_method = $_SERVER['REQUEST_METHOD'];

        if ($this->isJsonRequest()) {
            $this->_body = json_decode($this->_body, TRUE);
        }

        $this->_headers = apache_request_headers();

    }

    public function method() {
        return $this->_method;
    }

    public function header($key = NULL, $default = NULL) {
        if (is_null($key)) {
            return $this->_headers;
        }
        return isset($this->_headers[$key]) ? $this->_headers[$key] : $default;
    }

    public function isJsonRequest() {
        return isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json';
    }

    public function get($key = NULL, $default = NULL) {
        if (is_null($key)) {
            return $this->_query;
        }
        return isset($this->_query[$key]) ? $this->_query[$key] : $default;
        
    }

    public function post($key = NULL, $default = NULL) {
        $post = $this->isJsonRequest() ? $this->_body : $this->_post;

        if (is_null($key)) {
            return $post;
        }

        return isset($post[$key]) ? $post[$key] : $default;
    }

}