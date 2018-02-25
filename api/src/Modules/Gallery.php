<?php
namespace QTool\Api\Modules;

class Gallery extends \QTool\Api\Libs\Module {

    public function index() {
        $base = IMAGE_BASEPATH;
        $data = array();

        if (file_exists($base)) {
            $scan = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach($scan as $item) {
                if ($item->isDir()) {
                    $path = $item->getPath().'/'.$item->getFilename();
                    $path = str_replace('\\', '/', $path);
                    $path = str_replace($base, '', $path);
                    $text = ucwords(str_replace(array('\\', '/', '_'), array(' - ', ' - ', ' '), strtolower($path)));

                    $data[] = array(
                        'orig' => $path,
                        'path' => urlencode($path),
                        'text' => $text
                    );
                }
            }
        }
        
        return array(
            'data' => $data
        );
    }

    public function items() {
        $path = urldecode($this->request->get('path'));
        $base = IMAGE_BASEPATH.$path;
        $open = opendir($base);
        
        $data = array();

        if ($open) {

            while(FALSE !== ($item = readdir($open))) {
                if ($item != '.' && $item != '..' && $item != 'Thumbs.db') {
                    $item = urlencode($path.'/'.$item);
                    $data[] = [
                        'thumb' => 'api/gallery/thumb?path='.$item,
                        'image' => 'api/gallery/image?path='.$item
                    ];
                }
            }

            closedir($open);
        }

        return array(
            'data' => $data
        );
    }

    public function slides() {
        $path = urldecode($this->request->get('path'));
        $base = IMAGE_BASEPATH.$path;

        $maxw = $_GET['maxw'];
        $maxh = $_GET['maxh'];

        $open = opendir($base);
        $data = array();

        while(false !== ($name = readdir($open))) {
            if ($name == '.' || $name == '..' || $name == 'Thumbs.db') continue;
            $file = $base.'/'.$name;
            $mime = mime_content_type($file);

            $data[] = array(
                'src' => 'api/gallery/thumb?path='.urlencode($path.'/'.$name).'&maxw='.$maxw.'&maxh='.$maxh
            );
        }

        closedir($open);

        return array(
            'data' => $data
        );
    }

    public function image() {
        $file = IMAGE_BASEPATH.urldecode($this->request->get('path'));

        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $file);
            
        finfo_close($info);

        header('Content-Type: '.$mime);
        header('Cache-control: max-age='.(60*60*24*365));
        header('Expires: '.gmdate(DATE_RFC1123,time()+60*60*24*365));
        header('Last-Modified: '.gmdate(DATE_RFC1123,filemtime($file)));

        echo file_get_contents($file);
        exit();
    }

    public function thumb() {
        $file = IMAGE_BASEPATH.urldecode($this->request->get('path'));
        
        $maxw = isset($_GET['maxw']) ? (int)$_GET['maxw'] : 200;
        $maxh = isset($_GET['maxh']) ? (int)$_GET['maxh'] : 200;

        $file = str_replace('\(', '(', $file);
        $file = str_replace('\)', ')', $file);
        
        $this->loader->create('image', $file)->crop($maxw, $maxh);
    }

    public function grab() {
        $base = IMAGE_BASEPATH;

        $scan = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach($scan as $item) {
            if ($item->isDir()) {
                $path = $item->getPath().'/'.$item->getFilename();
                $path = str_replace('\\', '/', $path);
                $data[] = array(
                    'path' => $path,
                    'text' => str_replace($base, '', $path)
                );
            }
        }

        return array(
            'success' => TRUE,
            'data' => $data
        );
    }

}