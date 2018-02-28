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
                        'title' => $title,
                        'body' => $body,
                        'icon' => 'img/manifest/icon-48x48.png',
                        'click_action' => 'https://www.pusdikadm.xyz/qtool'
                    ),
                    'notification' => array(
                        'title' => $title,
                        'body' => $body
                    )
                )
                
            ),
            // array(
            //     'message' => array(
            //         'topic' => $topic,
            //         'notification' => array(
            //             'title' => $title,
            //             'body' => $body,
            //             'icon' => 'img/manifest/icon-48x48.png',
            //             'click_action' => 'https://www.pusdikadm.xyz/qtool'
            //         )
            //     )
            // ),
            array(
                'json' => TRUE,
                'headers' => array(
                    //'Authorization: key=AAAAQ-Oij8E:APA91bHxgRbdS9-KmD5EiqCKIq1hLf75pfa6mETtGYT05tGPEGvXqWRSfOjdPNiYLs6CHqL4Rw1xudf3FDVTWe1F215xnCGoSzUjm644XLruVRrLWzGzKrIgs8jljjRyq2my_AlE_roN'
                    'Authorization: Bearer '.$authorization
                )
            )
        ); 

        $data = json_decode($result, TRUE);
        $data = array_merge($data, $poster->info());

        return $data;
    }

    public function register() {
        $token = $this->request->post('token');
        // $storage = BASEPATH.'data/subscribers.json';
        // $data = json_decode(file_get_contents($storage), TRUE);

        // subscribe to global topic
        $poster = new Scrapper();
        $authorization = 'Authorization: key=AIzaSyDI_RrDDSLDGTtWgOvh0uAQJtyxK5wzcZI';
        $poster->post(

            // https://iid.googleapis.com/iid/v1/nKctODamlM4:CKrh_PC8kIb7O...clJONHoA/rel/topics/movies
            'https://iid.googleapis.com/iid/v1/'.$token.'/rel/topics/qtool', 
            NULL,
            array(
                'json' => TRUE,
                'insecure' => TRUE,
                'headers' => array(
                    $authorization
                )
            )
        );

        // if ( ! isset($data[$token])) {
        //     $data[$token] = array(); 
        //     $open = fopen($storage, 'w');

        //     if ($open) {
        //         fwrite($open, json_encode($data));
        //         fclose($open);
        //     }

            
        // }
        // 
        $data = $poster->info();

        return array(
            'data' => $data
        );
    }

}