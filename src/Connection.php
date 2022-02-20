<?php

namespace PHPSimpleDebugger;

class Connection
{
    private int $transaction_id;

    /**
     * @param $conn
     */
    function __construct(public $conn) {
        $this->transaction_id = 1;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        $messageString = "";
        while (true) {
            $chunk = stream_socket_recvfrom($this->conn, 1024, 0);
            $messageString .= $chunk;
            $len = strlen($messageString);
            if ($len === 0) {
                break;
            }
            if ($messageString[$len - 1] === "\0") {
                break;
            }
        }
        $messageStrings = explode("\0", $messageString);
        $messages = [];
        foreach ($messageStrings as $messageString) {
            if (str_starts_with($messageString, '<?xml')) {
                $message = Message::createFromXML($messageString);
                if ($message !== null) {
                    $messages[] = $message;
                }
            }
        }
        $responses = array_filter($messages, function(Message $message) {
            return !$message->isSkipped();
        });
        if (count($responses) === 0) {
            return array_merge($messages, $this->getMessages());
        }
        return $messages;
    }

    /**
     * @param Command $command
     * @return bool
     */
    public function sendCommand(Command $command): bool
    {
        if (!$command->isValid()) {
            return false;
        }
        stream_socket_sendto($this->conn, "${command}\0");
        $this->transaction_id++;
        return true;
    }

    public function close()
    {
        fclose($this->conn);
    }

    /**
     * @param string $input
     * @return Command
     */
    public function getCommand(string $input): Command
    {
        return new Command($input, $this->transaction_id);
    }
}
