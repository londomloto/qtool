<?php
namespace QTool\Api\Libs;

class Request {

    protected $_method;
    protected $_query;
    protected $_post;
    protected $_body;

    public function __construct() {
        $this->_query = $_GET;

        unset($this->_query['__route']);

        $this->_post = $_POST;
        $this->_body = file_get_contents('php://input');

        $this->_method = $_SERVER['REQUEST_METHOD'];

        if ($this->isJsonRequest()) {
            $this->_body = json_decode($this->_body, TRUE);
        }
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