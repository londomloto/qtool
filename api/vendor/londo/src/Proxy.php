<?php
namespace Londo;

class Proxy {

    public function connect() {
        $status = self::__run('status');

        if ($status == 'stopped') {
            
            $script = __DIR__.'\proxy\connect.bat';
            $command = 'powershell.exe "Start-Process '.$script.' -WindowStyle Hidden"';


            $proc = proc_open(
                $command,
                array(
                    0 => array('pipe', 'r'),
                    1 => array('pipe', 'w'),
                    2 => array('pipe', 'w')
                ),
                $pipes,
                NULL,
                NULL
            );

            foreach($pipes as $p) fclose($p);
            proc_close($proc);
            
            $proc = NULL;

            sleep(10);

            $status = self::__run('status');
        }

        return $status == 'running';
    }

    public function disconnect() {
        $status = self::__run('status');

        if ($status == 'running') {
            self::__run('disconnect');
            sleep(3);
            $status = self::__run('status');
        }

        return $status == 'stopped';
    }

    private static function __run($command) {
        $command = 'cd "'.__DIR__.'\proxy" && '.$command.'.bat';

        $proc = proc_open(
            $command,
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            ),
            $pipes,
            NULL,
            NULL
        );

        $result = '';

        if (is_resource($proc)) {
            while( ! feof($pipes[1])) {
                $result .= fgets($pipes[1]);
            }
        }

        foreach($pipes as $p) fclose($p);
        proc_close($proc);
        
        $proc = NULL;

        return trim($result);
    }

}