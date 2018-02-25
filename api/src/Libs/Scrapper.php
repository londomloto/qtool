<?php
namespace QTool\Api\Libs;

class Scrapper {

    protected $_remote;
    protected $_output;
    protected $_engine;
    protected $_info;

    public function get($remote, $options = array()) {

        $this->_remote = $remote;
        $this->_engine = curl_init();
        $this->_setup($options);
        $this->_output = curl_exec($this->_engine);
        $this->_info = curl_getinfo($this->_engine);
        
        curl_close($this->_engine);

        $this->_engine = NULL;

        return $this->_output;
    }

    public function info($key = NULL, $default = NULL) {
        if (is_null($key)) {
            return $this->_info;
        }
        return isset($this->_info[$key]) ? $this->_info[$key] : $default;
    }

    public function save($file) {
        $open = fopen($file, 'w');

        if ($open) {
            fwrite($open, $this->_output);
            fclose($open);
        }

        $open = NULL;

        return file_exists($file);
    }

    public function output() {
        return $this->_output;
    }

    protected function _setup($options) {
        curl_setopt($this->_engine, CURLOPT_URL, $this->_remote);
        curl_setopt($this->_engine, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        //curl_setopt($this->_engine, CURLOPT_REFERER, 'https://www.google.com');

        if (isset($options['range'])) {
            curl_setopt($this->_engine, CURLOPT_RANGE, $options['range']);
        }

        if (isset($options['writer'])) {
            curl_setopt($this->_engine, CURLOPT_WRITEFUNCTION, $options['writer']);
        } else {
            curl_setopt($this->_engine, CURLOPT_RETURNTRANSFER, TRUE);
        }

        if (isset($options['redirect'])) {
            curl_setopt($this->_engine, CURLOPT_FOLLOWLOCATION, TRUE);
        }

        if (isset($options['header'])) {
            curl_setopt($this->_engine, CURLOPT_HEADER, TRUE); 
        }

        if (isset($options['body']) && $options['body'] === FALSE) {
            curl_setopt($this->_engine, CURLOPT_NOBODY, TRUE); 
        }

        if (isset($options['binary'])) {
            curl_setopt($this->_engine, CURLOPT_BINARYTRANSFER, TRUE); 
        }

        if (stripos($this->_remote, 'https://') === 0 || isset($options['insecure'])) {
            curl_setopt($this->_engine, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($this->_engine, CURLOPT_SSL_VERIFYPEER, FALSE);
        }
    }
}