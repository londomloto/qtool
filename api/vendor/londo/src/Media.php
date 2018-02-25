<?php
namespace Londo;

class Media {

    private $__source;
    private $__headers;

    public function __construct($source) {
        $this->__source = $source;
        $this->__headers = array();
    }

    public function info() {
        static $info;

        if (is_null($info)) {
            $info = array(
                'size' => 0,
                'mime' => NULL
            );

            if ($this->__validateSource()) {

                $spec = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($spec, $this->__source);
                finfo_close($spec);

                $info['mime'] = $mime;

                if ($mime == 'video/mp4') {
                    $probe = self::__ffprobe()->format($this->__source);
                    
                    $info['duration'] = $probe->get('duration');
                    $info['duration_formatted'] = gmdate('H:i:s', $info['duration']);
                    $info['size'] = $probe->get('size');
                } else {
                    $info['size'] = filesize($this->__source);
                }
            }
        }

        return $info;
    }

    public function video() {
        static $video;

        if (is_null($video)) {
            $video = FALSE;

            if ($this->__validateSource()) {
                $video = self::__ffmpeg()->open($this->__source);
            }
        }

        return $video;
    }

    public function poster() {
        if ($this->__validateSource()) {
            $poster = dirname($this->__source).'/'.preg_replace('/\.mp4$/', '', basename($this->__source)).'.jpg';

            if ( ! file_exists($poster)) {
                if (($video = $this->video())) {
                    $tick = floor($info['duration'] / 2);    
                    $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($tick))->save($poster);
                }
            }

            return $poster;
        }

        return NULL;
    }

    public function stream() {  
        if ( ! $this->__validateSource()) {
            $this->__send404();
        }

        ob_clean();

        $range = $this->__resolveRangeRequest();
        $info = $this->info();

        if (FALSE !== $range) {
            
            $parts = explode('=', $range);

            if (strtolower($parts[0]) == 'bytes') {
                list($start, $end) = explode('-', $parts[1]);

                if (TRUE === empty($start)) {
                    $start = 0;
                }

                $start = (int)$start;

                if (TRUE === empty($end)) {
                    $end = $info['size'] - 1;
                }

                $end = (int)$end;
                $length = ($end - $start) + 1;

                $this->__setHeader('HTTP/1.1 206 Partial Content');
                $this->__setHeader('Accept-Ranges', 'bytes');
                $this->__setHeader('Content-Type', $info['mime']);
                $this->__setHeader('Content-Length', $length);
                $this->__setHeader('Content-Range', 'bytes '.$start.'-'.$end.'/'.$info['size']);

                $open = fopen($this->__source, 'rb');

                if ( ! $open) {
                    $this->__send500();
                }

                set_time_limit(0);
                $this->__sendHeaders();

                if ($start != 0) {
                    fseek($open, $start, SEEK_SET);
                }

                $chunk = 8192;

                while(true) {
                    if (ftell($open) >= $end) {
                        break;
                    }

                    echo fread($open, $chunk);
                    @ob_flush();
                    flush();
                }

                fclose($open);
            } else {
                $this->__send400();
            }
        } else {
            $this->__setHeader('Accept-Ranges', 'bytes');
            $this->__setHeader('Content-Type', $info['mime']);
            $this->__setHeader('Content-Length', $info['size']);

            $this->__sendHeaders();

            readfile($this->__source);

            @ob_flush();
            flush();
        }
        
        exit();
    }

    private static function __ffmpeg() {
        static $ffmpeg;

        if (is_null($ffmpeg)) {
            $ffmpeg = \FFMpeg\FFMpeg::create(array(
                'ffmpeg.binaries' => 'D:\softwares\developer\ffmpeg\3.4\bin\ffmpeg.exe',
                'ffprobe.binaries' => 'D:\softwares\developer\ffmpeg\3.4\bin\ffprobe.exe',
                'ffmpeg.threads' => 12
            ));
        }

        return $ffmpeg;
    }

    private static function __ffprobe() {
        return self::__ffmpeg()->getFFProbe();
    }

    private function __setHeader($key, $val = FALSE) {
        if (is_array($key)) {
            foreach($key as $k => $v) {
                $this->__setHeader($k, $v);
            }
        } else {
            if (FALSE !== $val) {
                $header = $key.': '.$val;
            } else {
                $header = $key;
            }
            $this->__headers[] = $header;
        }
    }

    private function __sendHeaders() {
        if ( ! headers_sent()) {
            
            foreach($this->__headers as $elem) {
                header($elem);
            }
        }
    }

    private function __send400() {
        header('HTTP/1.1 400 Invalid Request');
        exit();
    }

    private function __send404() {
        header('HTTP/1.1 404 Not Found');
        exit();
    }

    private function __send500() {
        header('HTTP/1.1 500 Internal Server Error');
        exit();
    }

    private function __validateSource() {
        static $valid;

        if (is_null($valid)) {
            $valid = file_exists($this->__source);
        }

        return $valid;
    }

    private function __resolveRangeRequest() {
        $range = FALSE;

        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
        } else if ($headers = apache_request_headers()) {
            $found = array_filter($headers, function($v, $k){ 
                return strtolower($k) == 'range'; 
            }, ARRAY_FILTER_USE_BOTH);

            if (isset($found[0])) {
                $range = $found[0];
            }
        }

        return $range;
    }
}