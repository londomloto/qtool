<?php

use Londo\Grabber as G;

class Grabber {

    public function grab() {
        set_time_limit(0);

        $browser = G::browser();
        $proxy = '';
        $deep = isset($_GET['deep']) ? $_GET['deep'] : TRUE;

        $result = new stdClass();
        $result->images = [];
        $result->links = [];
        $result->command = '';

        $url  = $_GET['url'];
        $loc = parse_url($url);

        if (preg_match('/(\.jpg$)/i', $url)) {
            $result->images[] = $url;
            print(json_encode($result));
            exit();
        }

        $file = BASEPATH.'temp/html_'.date('ymdhis');

        $spec = [
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        ];

        // $command = 'wget --ca-certificate=cacert.pem "'.$url.'" '.$proxy.' -O "'.$file.'"';
        $command = 'wget '.$browser.' --no-check-certificate "'.$url.'" '.$proxy.' -O "'.$file.'"';
        $result->command = $command;
        // echo $command;
        $process = proc_open($command, $spec, $pipes, null, null);

        if (is_resource($process)) {
            // while( ! feof($pipes[2])) {
            //     $line = fgets($pipes[2]);
            //     if (preg_match('/forbidden/i', $line)) {
            //         $result->proxy = Grabber::proxy();
            //     }
            // }
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        $content = '';

        if (file_exists($file)) {
            $content = file_get_contents($file);
            @unlink($file);
        }

        if ( ! empty($content)) {
            phpQuery::newDocument($content);

            // TRY...
            $thumbs = pq('[src$=jpg]')->add('[src*=.JPG]');

            foreach($thumbs as $dom) {
                $pq = pq($dom);

                if ($deep) {
                    if ( ! is_null($pq->attr('width'))) {
                        // it's just a thumbnail
                        $link = $pq->parent();

                        if ($link->is('a') && !is_null($link->attr('href'))) {
                            $href = $link->attr('href');

                            if (preg_match('#^/#', $href)) {
                                $href = $loc['scheme'].'://'.$loc['host'].$href;
                            } else if ( ! preg_match('#^http#', $href)) {
                                $href = preg_replace('#/$#', '', $url).'/'.$href;
                            }

                            $result->links[] = $href;
                        }
                    }
                }

                $im = $pq->attr('src');
                
                if ( ! empty($im)) {
                    if ( ! preg_match('/^https?:/', $im)) {
                        $im = $url.'/'.$im;
                    }
                    $result->images[] = $im;
                }
            }

            // TRY...
            $thumbs = pq('[href$=.jpg]')->add('[href$=.JPG]')->add('[href$=.jpeg]');
                
            foreach($thumbs as $dom) {
                $pq = pq($dom);
                $im = $pq->attr('href');
                if ( ! empty($im)) {
                    if ( ! preg_match('/^https?:/', $im)) {
                        $im = $url.'/'.$im;
                    }
                    $result->images[] = $im;
                }
            }   
            
        }

        return $result;

    }

    public function save() {
        set_time_limit(0);
        
        $result = new stdClass();
        $result->retry = false;
        $result->thumb = '';
        $result->image = '';

        $url  = trim($_GET['url']);

        $name = substr($url, strrpos($url, '/') + 1);
        $name = preg_replace('/\?.*/', '', $name);
        $name = str_replace('(', '_', $name);
        $name = str_replace(')', '_', $name);

        $path = urldecode($_GET['path']);
        $path = preg_replace('#[\/]$#', '', trim($path)).'/';
        $temp = BASEPATH.'temp/temp_'.date('ymdhis');

        $file = IMAGE_BASEPATH.$path.$name;
        $base = dirname($file);

        if ( ! file_exists($base)) {
            @mkdir($base, 0777, true);
        }

        if ( file_exists($file)) {
            $part = explode('.', $name);
            $part = array_pad($part, 2, '');
            $name = $part[0].'_'.date('ymdhis').'.'.$part[1];
            $file = IMAGE_BASEPATH.$path.$name;
        }

        $data = file_get_contents($url);

        if (file_exists($temp)) {
            file_put_contents($temp, $data);    
            @unlink($temp);
        }
        
        $html = '';

        if ( ! empty($data)) {
            if (($info = getimagesizefromstring($data))) {

                $width  = (float) $info[0];
                $height = (float) $info[1];

                if ($width >= 600 || $height >= 600) {
                    file_put_contents($file, $data);
                    
                    $result->image = 'api/gallery/image?path='.urlencode($path.$name).'&maxw='.$width.'&maxh='.$height;
                    $result->thumb = 'api/gallery/thumb?path='.urldecode($path.$name);

                }
            }
        } else {
            $result->retry = $url;
        }

        return $result;
    }

}