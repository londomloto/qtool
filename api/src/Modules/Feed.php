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

        $data = (new Scrapper())->post(
            'https://fcm.googleapis.com/v1/projects/qtool-196208/messages:send', 
            array(
                'topic' => 'qtool',
                'notification' => array(
                    'title' => 'B',
                    'body' => 'A'
                )
            ),
            array(
                'json' => TRUE,
                'headers' => array(
                    'Authorization: key=AIzaSyB9eqpS9EZYOk_9Yok8Rm-g3nBjqs0W7lw'
                )
            )
        );

        return array(
            'data' => json_decode($data)
        );
    }

    public function notify($topic) {
        $title = $this->request->post('title');
        $body = $this->request->post('body');
        $authorization = $this->request->post('authorization');

        $poster = new Scrapper();

        $result = $poster->post(
            'https://fcm.googleapis.com/v1/projects/qtool-196208/messages:send', 
            array(
                'message' => array(
                    'topic' => $topic,
                    'data' => array(
                        'body' => $body,
                        'title' => $title
                    )
                )
            ),
            array(
                'json' => TRUE,
                'headers' => array(
                    'Authorization: Bearer '.$authorization
                )
            )
        );

        return json_decode($result);
    }

    public function saveToken() {
        $token = $this->request->post('token');
        // $storage = BASEPATH.'data/subscribers.json';
        // $data = json_decode(file_get_contents($storage), TRUE);

        // subscribe to global topic
        $poster = new Scrapper();
        $poster->post('https://iid.googleapis.com/iid/v1/'.$token.'/rel/topics/qtool', array(), array(
            'Authorization' => 'key=AIzaSyB9eqpS9EZYOk_9Yok8Rm-g3nBjqs0W7lw'
        ));

        // if ( ! isset($data[$token])) {
        //     $data[$token] = array(); 
        //     $open = fopen($storage, 'w');

        //     if ($open) {
        //         fwrite($open, json_encode($data));
        //         fclose($open);
        //     }

            
        // }

        return array(
            'data' => $token
        );
    }

}