<?php
namespace QTool\Api\Libs;

class App {

    public $loader;
    public $request;
    public $response;

    public function __construct() {
        $this->request = new Request();
        $this->response = new Response();
        $this->loader = new Loader($this);
    }

    public function run() {

        $route = $_GET['__route'];
        $route = preg_replace('/(^\/|\/$)/', '', $route);

        $segments = explode('/', $route);
        $module = array_shift($segments);
        $action = array_shift($segments);

        if ( ! $module) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        if ( ! $action) {
            $action = 'index';
        }

        $params = $segments;
        $module = 'QTool\\Api\\Modules\\'.Text::camelize($module, '-', TRUE);

        $object = new $module($this);
        $object->app($this);

        ob_start();
        $result = call_user_func_array(array($object, $action), $params);
        $output = ob_get_contents();
        ob_end_clean();

        $this->response->output($output);
        $this->response->result($result);

        $this->response->send();
    }

}