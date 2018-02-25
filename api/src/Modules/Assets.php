<?php
namespace QTool\Api\Modules;

class Assets {

    public function download() {
        $path = urldecode($_GET['path']);
        $base = BASEPATH.$path;
        $name = basename($base);

        if (file_exists($base)) {

            $info = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $info['mime'];
            finfo_close($info);

            $size = filesize($base);

            header('Content-Type: '.$mime);
            header('Content-Length: '.$size);
            header('Content-Disposition: attachment; filename="'.$name.'"');
            readfile($base);

        } else {
            header('HTTP/1.1 404 Not Found');
            exit();
        }
    }

}