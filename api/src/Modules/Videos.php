<?php
namespace QTool\Api\Modules;

use QTool\Api\Libs\Media;
use QTool\Api\Libs\Proxy;
use QTool\Api\Libs\Hamster;
use QTool\Api\Libs\Scrapper;

class Videos extends \QTool\Api\Libs\Module {

    public function test() {
        
    }

    public function index() {

        $provider = $_GET['provider'];
        $proxy = $_GET['proxy'];

        $data = array();

        $opts = array(
            'provider' => $provider,
            'proxy' => $proxy
        );

        switch($provider) {
            case 'youtube':
                
                break;
            case 'xvideos':
                $data = $this->_fetch('http://www.xvideos.com/?k=cuckold', $opts);
                break;
            case 'xhamster':
                $data = $this->_fetch('https://xhamster.com/categories/cuckold', $opts);
                break;
            case 'local':
            default:

                $base = VIDEO_BASEPATH;
                $data = array();

                $scan = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach($scan as $item) {
                    if ( ! $item->isDir()) {
                        $name = $item->getFilename();
                        $type = $item->getExtension();
                        $path = str_replace('\\', '/', $item->getPath());
                        $path = str_replace($base, '', $path);

                        if ($type == 'mp4') {
                            $path = urlencode($path.'/'.$name);

                            $data[] = array(
                                'path' => 'api/videos/play?path='.$path,
                                'title' => $name,
                                'type' => 'video/mp4',
                                'play' => FALSE,
                                'load' => TRUE,
                                'poster' => 'api/videos/poster?path='.$path
                            );
                        }
                    }
                }

        }

        return array(
            'data' => $data
        );
    }

    public function poster() {
        $path = urldecode($_GET['path']);
        $base = VIDEO_BASEPATH.$path;

        $video = new Media($base);
        $poster = $video->poster();

        if ($poster) {
            $maxw = isset($_GET['maxw']) ? (int)$_GET['maxw'] : 168;
            $maxh = isset($_GET['maxh']) ? (int)$_GET['maxh'] : 94;

            $this->loader->create('image', $poster)->crop($maxw, $maxh);
        }
    }

    public function load() {
        $data = json_decode($_GET['video'], TRUE);
        $data['path'] = urldecode($data['path']);

        if ($data['load'] === FALSE) {
            $data = $this->_load($data);
        }

        return array(
            'data' => $data
        );
    }

    public function play() {
        $path = urldecode($_GET['path']);
        
        switch($_GET['provider']) {
            case 'xhamster':
                $video = new Hamster($path);
                $video->stream();
                break;
            case 'youtube':
                $path = array();
                $temp = array('__path', 'provider', 'proxy');

                foreach($_GET as $key => $val) {
                    if (in_array($key, $temp)) {
                        continue;
                    }
                    $path[] = $val;
                }

                $path = implode('&', $path);
                
                $video = new Hamster($path);
                $video->stream();

                break;
            case 'local':
            default:
                $base = VIDEO_BASEPATH.$path;
                $video = new Media($base);
                $video->stream();
        }
    }

    public function find() {

        $provider = $_GET['provider'];
        $proxy = $_GET['proxy'];
        $term = urldecode($_GET['term']);

        $data = array();

        $opts = array(
            'provider' => $provider,
            'proxy' => $proxy,
            'term' => $term
        );

        switch($provider) {
            case 'xhamster':
                $data = $this->_fetch('https://xhamster.com/search?q=cuckold+'.$term, $opts);
                break;
            case 'xvideos':
                $data = $this->_fetch('http://www.xvideos.com/?k=cuckold+'.$term, $opts);
                break;
            case 'local':
            default:
                $term = preg_replace_callback('/[.*+\[\]\(\)\{\}]/', function($m){ return '\\'.$m[0]; }, $term);
                $base = VIDEO_BASEPATH;

                $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base));
                $find = new \RegexIterator($iter, '/.*('.$term.').*\.mp4$/ui');
                
                $data = array();

                $base = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $base);

                foreach($find as $file) {
                    if ($file->isFile()) {
                        $name = $file->getFilename();
                        $path = $file->getPath().'/'.$name;
                        $path = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);
                        $path = str_replace($base, '', $path);

                        $item = array(
                            'path' => 'api/videos/play?path='.urlencode($path),
                            'title' => $name,
                            'type' => 'video/mp4',
                            'play' => FALSE,
                            'load' => TRUE,
                            'poster' => 'api/videos/poster?path='.urlencode($path)
                        );

                        $data[] = $item;
                    }
                }
        }

        return array(
            'data' => $data
        );
    }

    private function _load($data) {

        if ($data['provider'] == 'youtube') {
            $data['load'] = TRUE;
            $data['path'] = 'https://www.youtube.com/embed/'.$data['guid'].'?autoplay=1&disablekb=1&iv_load_policy=3&showinfo=0';

            $info = (new Scrapper())->get('https://www.googleapis.com/youtube/v3/videos?id='.$data['guid'].'&key=AIzaSyDCHEBx3OGgnHna2iLC4qtIl5IwL9T50LE&part=contentDetails');

            try {

                $json = json_decode($info, TRUE);

                if (isset($json['items']) && count($json['items']) > 0) {

                    $time = $json['items'][0]['contentDetails']['duration'];

                    if (preg_match_all('/(\d+)/', $time, $matches)) {

                        $match = $matches[0];
                        $count = count($match);

                        if ($count == 1) {
                            array_unshift($match, '0', '0');
                        } else if ($count == 2) {
                            array_unshift($match, '0');
                        }

                        $secInit = (int)$match[2];
                        $sec = ($secInit % 60);
                        $secOver = floor($secInit/60);

                        $minInit = (int)$match[1] + $secOver;
                        $min = ($minInit % 60);
                        $minOver = floor($minInit/60);

                        $hours = $match[0] + $minOver;

                        if ($hours != 0) {
                            $time = sprintf("%'.02d:%'.02d:%'.02d", $hours, $min, $sec);
                        } else {
                            $time = sprintf("%'.02d:%'.02d", $min, $sec);
                        }

                    }

                    $data['duration'] = $time;

                }
                

            } catch(\Exception $ex){}
            /*
            // get info
            parse_str(file_get_contents('https://www.youtube.com/get_video_info?video_id='.$data['guid']), $parsed);
            
            // $streams = $parsed['url_encoded_fmt_stream_map'];
            $streams = $parsed['adaptive_fmts'];
            $streams = explode(',', $streams);

            $links = array();

            foreach($streams as $e) {
                parse_str($e, $e);
                foreach($e as $k => $v) {
                    if ($k == 'url') {
                        $v = urldecode($v);
                        if (strpos($v, 'video/mp4') !== FALSE) {
                            $links[] = $v;    
                        }
                    }
                }
            }

            if (count($links) > 0) {
                $path = array_pop($links);
                $path = 'api/videos/play?path='.$path.'&provider='.$data['provider'].'&proxy='.$data['proxy'];
                $data['path'] = $path;
            }*/
        } else {

            $content = (new Scrapper())->get($data['path']);
            
            if ($content) {
                
                \phpQuery::newDocument($content);

                switch($data['provider']) {
                    case 'xhamster':

                        $text = pq('#initials-script')->html();

                        $data['load'] = TRUE;

                        if (preg_match('#"title":"([^"]+)"#', $text, $match)) {
                            $data['title'] = str_replace('\\', '', $match[1]);
                        }

                        if (preg_match('#"mp4File":"([^"]+)"#', $text, $match)) {
                            $path = str_replace('\\', '', $match[1]);
                            $data['path'] = 'api/videos/play?path='.urlencode($path).'&provider='.$data['provider'].'&proxy='.$data['proxy'];
                        }

                        if (preg_match('#"poster":"([^"]+)"#', $text, $match)) {
                            $data['poster'] = str_replace('\\', '', $match[1]);
                        }

                        break;

                    case 'xvideos':

                        $data['load'] = TRUE;

                        if (preg_match('/html5player\.setVideoUrlLow\(([^\)]+)\)/', $content, $match)) {
                            $path = substr(substr($match[1], 1), 0, -1);
                            $data['path'] = $path;
                        }

                        break;
                }
            }
        }

        return $data;
    }

    protected function _fetch($url, $options = array()) {
        set_time_limit(0);

        if ($options['proxy']) {
            Proxy::connect();
        } else {
            Proxy::disconnect();
        }

        $content = (new Scrapper())->get($url);

        $data = array();

        if ( ! empty($content)) {

            $href = parse_url($url);

            \phpQuery::newDocument($content);

            switch($options['provider']) {
                case 'xhamster':

                    $query = pq('div.video-thumb');

                    foreach($query as $node) {
                        $context = pq($node);

                        $item = array(
                            'play' => FALSE,
                            'load' => FALSE,
                            'type' => 'video/mp4',
                            'proxy' => $options['proxy'],
                            'provider' => $options['provider']
                        );

                        $pq = pq($context->find('.video-thumb-info__name')->get(0));
                        $item['title'] = $pq->html();
                        $item['path'] = urlencode($pq->attr('href'));

                        $pq = pq($context->find('.thumb-image-container__duration')->get(0));
                        $item['duration'] = $pq->html();

                        $pq = pq($context->find('.thumb-image-container__image')->get(0));
                        $item['poster'] = $pq->attr('src');


                        $data[] = $item;
                    }

                    break;
                case 'xvideos':

                    $query = pq('div.thumb-block');

                    foreach($query as $node) {
                        $context = pq($node);

                        $item = array(
                            'play' => FALSE,
                            'load' => FALSE,
                            'type' => 'video/mp4',
                            'proxy' => $options['proxy'],
                            'provider' => $options['provider']
                        );

                        $pq = pq($context->find('p:not(.metadata) > a')->get(0));
                        $item['title'] = $pq->attr('title');

                        $path = $pq->attr('href');

                        if (preg_match('/^\//', trim($path))) {
                            $base = $href['scheme'].'://'.$href['host'].$href['path'];
                            $path = preg_replace('/\/$/', '', $base).$path;
                        }

                        $item['path'] = $path;

                        $pq = pq($context->find('img')->get(0));
                        $item['poster'] = $pq->attr('data-src');

                        $pq = pq($context->find('.duration')->get(0));
                        $item['duration'] = $pq->html();



                        $data[] = $item;
                    }

                    break;
            }
        }

        return $data;
    }
}