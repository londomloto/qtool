<?php

use Londo\Media;

class Sounds {

    public function index() {
        $base = AUDIO_BASEPATH;

        $scan = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach($scan as $item) {
            if ($item->isDir()) {
                $path = $item->getPath().'/'.$item->getFilename();
                $path = str_replace('\\', '/', $path);
                $path = str_replace($base, '', $path);
                $text = ucwords(str_replace(array('\\', '/', '_'), array(' - ', ' - ', ' '), strtolower($path)));

                $data[] = array(
                    'path' => urlencode($path),
                    'text' => $text
                );
            }
        }

        return array(
            'data' => $data
        );
    }

    public function items() {
        $path = urldecode($_GET['path']);
        $base = AUDIO_BASEPATH.$path;

        $scan = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $data = array();

        foreach($scan as $item) {
            if ( ! $item->isDir() && $item->getExtension() == 'mp3') {
                $name = $item->getFilename();

                $data[] = array(
                    'path' => 'api/sounds/play?path='.urlencode($path.'/'.$name),
                    'text' => $name,
                    'play' => FALSE
                );
            }
        }

        return array(
            'data' => $data
        );
    }

    public function play() {
        $path = urldecode($_GET['path']);
        $base = AUDIO_BASEPATH.$path;

        $audio = new Media($base);
        $audio->stream();
    }
    
}