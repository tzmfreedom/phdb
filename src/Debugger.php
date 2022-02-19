<?php

namespace PHPSimpleDebugger;

class Debugger
{
    private bool $debug;

    /**
     * @param false|bool $debug
     */
    function __construct(bool $debug = false)
    {
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
            while ($res = stream_socket_accept($socket, -1)) {
                $conn = new Connection($res, $this->debug);
                $conn->getMessages();
                $conn->sendCommand("stdout");
                $conn->getMessages();

//                $conn->sendCommand("breakpoint_set $file $lineno");
//                $conn->getMessages();

                $conn->sendCommand("continue");
                while(true) {
                    $messages = $conn->getMessages();
                    foreach ($messages as $xml) {
                        $response = Message::createFromXML($xml);
                        if ($response::class === ResponseMessage::class) {
                            if ($response->status === 'stopping') {
                                break 2;
                            }
                        }
                        $this->handleResponse($response);
                        if ($response::class === StreamMessage::class) {
                            continue;
                        }
                        while(true) {
                            $input = readline(">> ");
                            if ($input === "exit") {
                                break 3;
                            }
                            if ($input === '') {
                                continue;
                            }
                            $conn->sendCommand($input);
                            break;
                        }
                    }
                }
                $conn->close();
            }
        }

        fclose($socket);
    }

    /**
     * @param InitMessage|StreamMessage|ResponseMessage|null $message
     */
    private function handleResponse(InitMessage|StreamMessage|ResponseMessage|null $message)
    {
        if ($message === null) {
            return;
        }
//        var_dump($message);
        switch($message::class){
            case InitMessage::class:
                return;
            case StreamMessage::class:
                echo $message->body;
                break;
            case ResponseMessage::class:
                if (isset($message->property)) {
                    var_dump($message->property);
                }
                if (!empty($message->lineno)) {
                    $lineno = $message->lineno;
                    $file = $message->filename;
                    if (!empty($file)) {
                        echo "$file at $lineno\n";
                        $this->showFile($file, (int)$lineno);
                    }
                    break;
                }
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
        $current = 0;
        $cnt = $lineno < 8 ? 0 : $lineno - 8;
        for ($i = 0; $i < $cnt; $i++) {
            fgets($fp);
            $current++;
        }
        $max = $lineno + 7;
        $width = strlen((string)$max);
        for ($i = 0; $i < $size; $i++) {
            $line = fgets($fp);
            $current++;
            if ($line) {
                $formatLine = sprintf("%0{$width}d", $current);
                if ($current === $lineno) {
                    echo ">> $formatLine: $line";
                } else {
                    echo "   $formatLine: $line";
                }
            }
        }
        fclose($fp);
    }

}

