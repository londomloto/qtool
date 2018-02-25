<?php
namespace QTool\Api\Modules;

class Feed extends \QTool\Api\Libs\Module {

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
        }

        return array(
            'data' => $data
        );
    }

}