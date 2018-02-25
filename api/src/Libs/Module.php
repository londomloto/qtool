<?php
namespace QTool\Api\Libs;

class Module {

    protected $_app;

    public function app(App $app = NULL) {
        if ( ! is_null($app)) {
            $this->_app = $app;
        }
        return $this->_app;
    }

    public final function __get($prop) {
        if (property_exists($this->_app, $prop)) {
            return $this->_app->$prop;
        }
        return $this->_app->loader->load($prop);
    }

}