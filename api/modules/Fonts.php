<?php

class Fonts {

    public function grab() {
        $result = array(
            'success' => FALSE
        );

        $source = trim($_GET['source']);
        $bundle = trim($_GET['bundle']);

        if (preg_match('/url\((.*)\)/', $source, $matches)) {
            $source = $matches[1];
        } else if (preg_match("/href='([^']+)'/", $source, $matches)) {
            $source = $matches[1];
        }

        if ( ! empty($source)) {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.167 Safari/537.36\r\n"
                )
            ));

            $content = file_get_contents($source, false, $context);

            if ( ! empty($content)) {

                $array = array();

                $css = preg_replace_callback(
                    '/url\((.*)\)\s+/', 
                    function($match) use (&$array) {
                        $parts = explode('/', $match[1]);
                        $font  = array_pop($parts);
                        $array[$font] = $match[1];
                        return "url(fonts/$font) ";
                    }, 
                    $content
                );

                if ( ! empty($array)) {

                    $base = FONTS_BASEPATH.$bundle;
                    $path = $base.'/fonts';

                    if ( ! file_exists($path)) {
                        @mkdir($path, 0777, true);
                    }

                    $fh = fopen($base.'/fonts.css', 'w');
                    fwrite($fh, $css);
                    fclose($fh);

                    foreach($array as $name => $url) {
                        $fh = fopen("{$path}/{$name}", 'w');
                        fwrite($fh, file_get_contents($url, false, $context));
                        fclose($fh);
                    }

                    $dest = "{$base}.zip";

                    $zip = new ZipArchive();

                    if ($zip->open($dest, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
                        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base), RecursiveIteratorIterator::SELF_FIRST);    

                        foreach($iterator as $obj) {
                            $name = $obj->getFileName();
                            $file = $obj->getPathName();
                            if ( ! $obj->isDir()) {
                                $zip->addFile($file, substr($file, 5));    
                            }
                        }

                        $zip->close();

                        /*header('Pragma: public');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Last-Modified: '.gmdate ('D, d M Y H:i:s', filemtime ($dest)).' GMT');
                        header('Cache-Control: private',false);
                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="'.basename($dest).'"');
                        header('Content-Transfer-Encoding: binary');
                        header('Content-Length: '.filesize($dest));
                        header('Connection: close');
                        readfile($dest);

                        exit();*/

                    }

                    $result['success'] = TRUE;
                    $result['data'] = array(
                        'archive' => 'api/assets?path=fonts/'.$bundle.'.zip',
                        'content' => $content
                    );

                }

            }
        }

        return $result;
    }

}