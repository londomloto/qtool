<?php
namespace QTool\Api\Modules;

use QTool\Api\Libs\Scrapper;

class Grabber extends \QTool\Api\Libs\Module {

    public function grab() {
        $url = $this->request->get('url');
        $loc = parse_url($url);
        $deep = $this->request->get('deep');

        $content = (new Scrapper())->get($url);
        
        $data = array(
            'links' => array(),
            'images' => array()
        );
        
        if ( ! empty($content)) {
            \phpQuery::newDocument($content);

            // TRY...
            $thumbs = pq('[src$=jpg]')->add('[src*=.JPG]');

            foreach($thumbs as $dom) {
                $pq = pq($dom);

                if ($deep == 1) {
                    
                    $thumb = FALSE;
                    $class = $pq->attr('class');
                    $width = $pq->attr('width');

                    if ( ! is_null($width)) {
                        $thumb = TRUE;
                    } else if ( ! is_null($class) && preg_match('/(small|thumb|preview)/', $class)) {
                        $thumb = TRUE;
                    }

                    if ($thumb) {
                        $link = $pq->parent();

                        if ($link->is('a') && !is_null($link->attr('href'))) {
                            $href = $link->attr('href');

                            if (preg_match('#^/#', $href)) {
                                $href = $loc['scheme'].'://'.$loc['host'].$href;
                            } else if ( ! preg_match('#^http#', $href)) {
                                $href = preg_replace('#/$#', '', $url).'/'.$href;
                            }

                            $data['links'][] = $href;
                        }
                    }
                }
                
                $image = $pq->attr('src');
                
                if ( ! empty($image)) {
                    if ( ! preg_match('/^https?:/', $image)) {
                        $image = $url.'/'.$image;
                    }

                    if ( ! preg_match('/preview/', $image)) {
                        $data['images'][] = $image;    
                    }
                }
            }

            // TRY...
            $thumbs = pq('[href$=.jpg]')->add('[href$=.JPG]')->add('[href$=.jpeg]');
                
            foreach($thumbs as $dom) {
                $pq = pq($dom);
                $image = $pq->attr('href');
                if ( ! empty($image)) {
                    if ( ! preg_match('/^https?:/', $image)) {
                        $image = $url.'/'.$image;
                    }
                    $data['images'][] = $image;
                }
            }   
        }

        return array(
            'data' => $data
        );
    }

    public function save() {
        set_time_limit(0);

        $data = array(
            'retry' => FALSE,
            'thumb' => '',
            'image' => ''
        );

        $url = urldecode($this->request->get('url'));

        $name = basename($url);
        $name = preg_replace('/[^\w.-]/', '_', $name);
        $path = urldecode($this->request->get('path'));
        $path = preg_replace('/\/$/', '', trim($path)).'/';

        $file = IMAGE_BASEPATH.$path.$name;
        $base = dirname($file);

        if ( ! file_exists($base)) {
            @mkdir($base, 0777, TRUE);
        }

        if ( file_exists($file)) {
            $part = explode('.', $name);
            $part = array_pad($part, 2, '');
            $name = $part[0].'_'.date('ymdhis').'.'.$part[1];
            $file = IMAGE_BASEPATH.$path.$name;
        }

        $content = (new Scrapper())->get($url);

        if ( ! empty($content)) {

            if (($info = getimagesizefromstring($content))) {

                $width  = (float) $info[0];
                $height = (float) $info[1];

                if ($width >= 600 || $height >= 600) {
                    file_put_contents($file, $content);
                    
                    $encoded = urlencode($path.$name);

                    $data['image'] = 'api/gallery/image?path='.$encoded.'&maxw='.$width.'&maxh='.$height;
                    $data['thumb'] = 'api/gallery/thumb?path='.$encoded;

                }
            }
        } else {
            $data['retry'] = $url;
        }

        return array(
            'data' => $data
        );
    }

}