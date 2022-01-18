<?php

namespace PHPSimpleDebugger;

class Debugger
{
    private int $transaction_id;
    private bool $debug;

    /**
     * @param false|bool $debug
     */
    function __construct(bool $debug = false)
    {
        $this->transaction_id = 1;
        $this->debug = $debug;
    }

    /**
     * @param int $port
     */
    public function run(int $port = 9000)
    {
        $socket = stream_socket_server("tcp://0.0.0.0:${port}", $errno, $errstr);
        if (!$socket) {
            echo "${errstr} (${errno})\n";
            die('Could not create socket');
        }

        while (true) {
            while ($conn = stream_socket_accept($socket, -1)) {
                $this->getMessages($conn); // get initial
                $this->sendCommand($conn, "stdout");
                $this->getMessages($conn); // get strem
                $this->sendCommand($conn, "step");

                while(true) {
                    $messages = $this->getMessages($conn);
                    foreach ($messages as $xml) {
                        $response = Response::createFromXML($xml);
                        if (isset($response['status']) && $response['status'] == 'stopping') {
                            break 2;
                        }
                        $this->handleResponse($response);
                        if ($response['_type'] === 'stream') {
                            continue;
                        }
                        $input = readline(">> ");
                        if ($input == "exit") {
                            break 2;
                        }
                        $this->sendCommand($conn, $input);
                    }
                }
                fclose($conn);
            }
        }

        fclose($socket);
    }

    /**
     * @param resource $conn
     * @return array
     */
    private function getMessages($conn): array
    {
        $message = "";
        while (true) {
            $chunk = stream_socket_recvfrom($conn, 1024, 0);
            $message .= $chunk;
            $len = strlen($message);
            if ($message[$len - 1] == "\0") {
                break;
            }
        }
        if ($this->debug) {
            echo $message;
        }
        $messages = explode("\0", $message);
        $ret = [];
        foreach ($messages as $m) {
            if (strpos($m, '<?xml') === 0) {
                $ret[] = $m;
            }
        }
        return $ret;
    }

    /**
     * @param array $obj
     */
    private function handleResponse(array $obj)
    {
        switch($obj["_type"]){
            case 'init':
                // $file = $obj['fileuri'];
                // $lineno = $obj['lineno'];
                return;
            case 'stream':
                echo $obj['body'] . PHP_EOL;
                break;
            case 'response':
                if (isset($obj['property'])) {
                    echo $obj['property']['body'] . PHP_EOL;
                }
                if (isset($obj['xdebug:message'])) {
                    $file = $obj['xdebug:message']['filename'];
                    $lineno = $obj['xdebug:message']['lineno'];
                    break;
                }
        }
        if (isset($file) && $file !== '') {
            echo "$file at $lineno\n";
            $this->showFile($file, (int)$lineno);
        }
    }

    /**
     * @param string $file
     * @param int $lineno
     */
    private function showFile(string $file, int $lineno)
    {
        $fp = fopen($file, 'r');
        $size = 15;
        $cnt = $lineno < 7 ? 0 : $lineno - 7;
        $current = 1;
        while ($cnt > 0) {
            fgets($fp);
            $cnt--;
            $current++;
        }
        while ($line = fgets($fp)) {
            if ($current == $lineno) {
                echo ">> $line";
            } else {
                echo "   $line";
            }
            $size--;
            $current++;
            if ($size == 0) {
                break;
            }
        }
        fclose($fp);
    }

    /**
     * @param $conn
     * @param string $input
     */
    private function sendCommand($conn, string $input)
    {
        if (in_array($input, ["n", "next"])) {
            $command = "step_over -i $this->transaction_id";
        } else if (in_array($input, ["c", "continue"])) {
            $command = "run -i $$this->transaction_id";
        } else if (in_array($input, ["s", "step"])) {
            $command = "step_into -i $$this->transaction_id";
        } else if (in_array($input, ["status"])) {
            $command = "status -i $$this->transaction_id";
        } else if (in_array($input, ["source"])) {
            $command = "source -i $$this->transaction_id";
        } else if (in_array($input, ["stdout"])) {
            $command = "stdout -i $$this->transaction_id -c 1";
        } else if (in_array($input, ["w", "whereami"])) {
            return;
        } else {
            $b64encoded = base64_encode($input);
            $command = "eval -i $$this->transaction_id -- ${b64encoded}";
        }

        stream_socket_sendto($conn, "${command}\0");

        $this->transaction_id++;
    }
}

