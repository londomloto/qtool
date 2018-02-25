<?php
namespace QTool\Api\Libs;

class Loader {

    protected $_app;
    protected $_cached;

    public function __construct(App $app) {
        $this->_app = $app;
        $this->_cached = array();
    }
    
    public function create() {
        $args = func_get_args();
        $name = array_shift($args);
        return $this->load($name, $args, FALSE);
    }

    public function load($name, $args = NULL, $shared = TRUE) {
        if ($shared) {
            if (isset($this->_cached[$name])) {
                return $this->_cached[$name];
            }
        }

        $class = 'QTool\\Api\\Libs\\'.Text::camelize($name);

        if ( ! is_null($args)) {
            $ref = new \ReflectionClass($class);
            $instance = $ref->newInstanceArgs($args);
        } else {
            $instance = new $class();
        }

        $instance->app($this->_app);

        if ($shared) {
            $this->_cached[$name] = $instance;
        }

        return $instance;
    }

}