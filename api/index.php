<?php
require_once 'vendor/autoload.php';

use QTool\Api\Libs\App;

// consts
define('BASEPATH', dirname(__DIR__).'/');

define('AUDIO_BASEPATH', 'D:/musics/');
define('IMAGE_BASEPATH', 'D:/pictures/collections/adult/');
define('FONTS_BASEPATH', BASEPATH.'fonts/');
define('VIDEO_BASEPATH', 'D:/videos/Adult/');

$app = new App();
$app->run();