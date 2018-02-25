<?php
namespace QTool\Api\Modules;

use QTool\Api\Libs\Scrapper;

class Feed extends \QTool\Api\Libs\Module {

    public function post() {
        return array(
            'success' => TRUE,
            'data' => $this->request->post()
        );
    }

    public function test() {

        echo (new Scrapper())->post('http://127.0.0.1/qtool/api/feed/post', array(
            'foo' => 'foo',
            'bar' => 'bar'
        ));

    }

    public function saveToken() {
        $token = $this->request->post('token');
        $storage = BASEPATH.'data/subscribers.json';
        $data = json_decode(file_get_contents($storage), TRUE);

        if ( ! isset($data[$token])) {
            $data[$token] = array(); 
            $open = fopen($storage, 'w');

            if ($open) {
                fwrite($open, json_encode($data));
                fclose($open);
            }

            // subscribe to global topic
            $poster = new Scrapper();
            $poster->post('https://iid.googleapis.com/iid/v1/'.$token.'/rel/topics/qtool', array(), array(
                'Authorization' => 'key=AIzaSyB9eqpS9EZYOk_9Yok8Rm-g3nBjqs0W7lw'
            ));
        }

        return array(
            'data' => $data
        );
    }

}