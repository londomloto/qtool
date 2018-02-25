<?php

require_once 'vendor/autoload.php';

use Londo\Text,
    Londo\Util;

// consts
define('BASEPATH', dirname(__DIR__).'/');
define('AUDIO_BASEPATH', 'D:/musics/');
define('IMAGE_BASEPATH', 'D:/pictures/collections/adult/');
define('FONTS_BASEPATH', BASEPATH.'fonts/');
define('VIDEO_BASEPATH', 'D:/videos/Adult/');

// fetch available api's
$modules = array();

$items = new DirectoryIterator(__DIR__.'/modules/');

foreach($items as $item) {
    if ($item->isDot()) continue;

    $file = $item->getFilename();
    $name = Text::uncamelize(str_replace('.php', '', $file));
    $handler = str_replace('.php', '', $file);

    $modules[$name] = array(
        'handler' => $handler,
        'path' => __DIR__.'/modules/'.$file
    );

}

$path = isset($_REQUEST['__path']) ? $_REQUEST['__path'] : FALSE;

if ($path) {

    $segments = explode('/', $path);
    $name = array_shift($segments);
    $method = array_shift($segments);

    if (isset($modules[$name])) {
        $module = $modules[$name];

        require_once($module['path']);

        $control = new $module['handler']();

        if (empty($method)) {
            $method = 'index';
        }

        $output = call_user_func_array(array($control, $method), $segments);

        if ( ! is_null($output)) {

            if (is_array($output) || is_object($output)) {
                header('Content-Type: application/json');
                $output = json_encode(Util::fixUTF8($output), JSON_PRETTY_PRINT);
            }

            echo $output;
            exit();
        }
    } else {
        header('HTTP/1.1 404 Not Found');
        exit();
    }
}