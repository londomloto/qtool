<?php
namespace QTool\Api\Libs;

class Image extends Library {

    protected $_image;

    public function __construct($image) {
        $this->_image = $image;
    }

    public function cache($image) {
        if (file_exists($image)) {
            $etag = md5_file($image);
            $lastModified = gmdate('D, d M Y H:i:s \G\M\T', filemtime($image));

            $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) : FALSE;
            $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']) : FALSE;

            if (FALSE !== $ifNoneMatch) {
                $ifNoneMatch = strtolower(str_replace('"', '', $ifNoneMatch));
            }

            if (
                isset($_SERVER['HTTP_ACCEPT_ENCODING']) && 
                FALSE !== strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && 
                FALSE !== $ifNoneMatch
            ) {
                $ifNoneMatch = str_replace('-gzip', '', $ifNoneMatch);
            }

            $cached = TRUE;

            if ( ! $ifModifiedSince && ! $ifNoneMatch) {
                $cached = FALSE;
            }

            if ($ifNoneMatch && $ifNoneMatch != $etag) {
                $cached = FALSE;
            }

            if ($ifModifiedSince && $ifModifiedSince != $lastModified) {
                $cached = FALSE;
            }

            if ( ! $cached) {
                @unlink($image);
                return;
            }

            $this->response->send304();
        }
    }

    public function crop($width = 100, $height = 100) {

        if ( ! file_exists($this->_image)) {
            $this->response->send404();
        }

        set_time_limit(0);

        if ($info = @getimagesize($this->_image)) {

            $mime = $info['mime'];
            
            $originalWidth  = $info[0];
            $originalHeight = $info[1];

            if ($width >= $originalWidth && $height >= $originalHeight) {
                $width  = $originalWidth;
                $height = $originalHeight;
            }

            $offsetX = 0;
            $offsetY = 0;

            $originalRatio = $originalWidth / $originalHeight;
            $cropRatio = $originalWidth < $originalHeight ? ($width / $height) : ($height / $width);

            if ($originalRatio < $cropRatio) {
                $temp = $originalHeight;
                $originalHeight = $originalWidth / $cropRatio;
                $offsetY = ($temp - $originalHeight) / 2;
            } else if ($originalRatio > $cropRatio) {
                $temp = $originalWidth;
                $originalWidth = $originalHeight / $cropRatio;
                $offsetX = ($temp - $originalWidth) / 2;
            }

            $ratioX  = $width / $originalWidth;
            $ratioY  = $height / $originalHeight;

            if ($ratioX * $originalHeight < $height) {
                $cropWidth = $width;
                $cropHeight = ceil($ratioX * $originalHeight);
            } else {
                $cropWidth = ceil($ratioY * $originalWidth);
                $cropHeight = $height;
            }

            $target = imagecreatetruecolor($cropWidth, $cropHeight);
            $source = imagecreatefromjpeg($this->_image);
            $quality = 100;

            imagecopyresampled(
                $target, 
                $source, 
                0, 
                0, 
                $offsetX, 
                $offsetY, 
                $cropWidth, 
                $cropHeight, 
                $originalWidth, 
                $originalHeight
            );

            ob_start();
            imagejpeg($target, NULL, $quality);
            $content = ob_get_contents();
            ob_end_clean();

            imagedestroy($target);
            imagedestroy($source);
            
            // cache on the browser
            header('Pragma: public');
            header('Cache-Control: max-age=86400');
            header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
            header('Content-Type: '.$mime);
            header('Content-Length: '.strlen($content));

            echo $content;
            exit();
        } else {
            $this->response->send415();
        }
    }

}