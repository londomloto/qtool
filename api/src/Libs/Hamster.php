<?php
namespace QTool\Api\Libs;

class Hamster {

    public function __construct($url) {
        $this->url = $url;
    }

    public function range() {
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

    public function stream() {
        set_time_limit(0);

        $size = $this->size();
        $range = $this->range();
        
        $func = function($curl, $data) {
            $length = strlen($data);
            echo $data;
            @ob_flush();
            flush();
            return $length;
        };

        if (FALSE !== $range) {

            $parts = explode('=', $range);

            if (strtolower($parts[0]) == 'bytes') {
                list($start, $end) = explode('-', $parts[1]);

                if (TRUE === empty($start)) {
                    $start = 0;
                }

                $start = (int)$start;

                if (TRUE === empty($end)) {
                    $end = $size - 1;
                }

                $end = (int)$end;
                $length = ($end - $start) + 1;

                header('HTTP/1.1 206 Partial Content');
                header('Accept-Ranges: bytes');
                header('Content-Type: video/mp4');
                header('Content-Length: '.$length);
                header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);

                $this->chunk($start, $end, $func);
            } else {
                header('HTTP/1.1 400 Invalid Request');
            }
        } else {

            header('Accept-Ranges: bytes');
            header('Content-Type: video/mp4');
            header('Content-Length: '.$size);

            $i = 0;
            $chunk = 8192;

            while( $i <= $size ) {
                $this->chunk( (($i==0)?$i:$i+1) , ((($i+$chunk)>$size)?$size:$i+$chunk), $func );
                $i = ($i + $chunk);
            }
        }

        exit();
    }

    public function chunk($start, $end, $func) {
        (new Scrapper())->get($this->url, array(
            'redirect' => TRUE,
            'insecure' => TRUE,
            'binary' => TRUE,
            'writer' => $func,
            'range' => $start.'-'.$end
        ));
    }

    public function size() {

        $scrapper = new Scrapper();
        
        $scrapper->get($this->url, array(
            'binary' => TRUE,
            'redirect' => TRUE,
            'insecure' => TRUE,
            'header' => TRUE,
            'body' => FALSE
        ));

        $size = $scrapper->info('download_content_length');
        return intval($size);
    }

}