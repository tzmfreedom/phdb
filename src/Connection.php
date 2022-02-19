<?php

namespace PHPSimpleDebugger;

class Connection
{
    private int $transaction_id;

    function __construct(
        public $conn,
        public $debug
    ) {
        $this->transaction_id = 1;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        $message = "";
        while (true) {
            $chunk = stream_socket_recvfrom($this->conn, 1024, 0);
            $message .= $chunk;
            $len = strlen($message);
            if ($len === 0) {
                break;
            }
            if ($message[$len - 1] == "\0") {
                break;
            }
        }
        if ($this->debug) {
            echo $message. PHP_EOL;
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
     * @param string $input
     * @return string
     */
    private function getCommand(string $input): string
    {
        $args = preg_split('/\s+/', $input);
        $command = $args[0];
        if (in_array($command, ["n", "next"], true)) {
            return "step_over -i $this->transaction_id";
        }
        if (in_array($command, ["c", "continue"], true)) {
            return "run -i $this->transaction_id";
        }
        if (in_array($command, ["s", "step"], true)) {
            return "step_into -i $this->transaction_id";
        }
        if (in_array($command, ["status"], true)) {
            return "status -i $this->transaction_id";
        }
        if (in_array($command, ["source"], true)) {
            return "source -i $this->transaction_id";
        }
        if (in_array($command, ["stdout"], true)) {
            return "stdout -i $this->transaction_id -c 1";
        }
        if (in_array($command, ["breakpoint_set"], true)) {
            $file = $args[1];
            $lineno = $args[2];
            return "breakpoint_set -i $this->transaction_id -t line -f $file -n $lineno";
        }
        if (in_array($command, ["breakpoint_get"], true)) {
            $id = $args[1];
            return "breakpoint_get -i $this->transaction_id -d $id";
        }
        if (in_array($command, ["breakpoint_list"], true)) {
            return "breakpoint_list -i $this->transaction_id";
        }
        if (in_array($input, ["w", "whereami"], true)) {
            return "";
        }
        $b64encoded = base64_encode($input);
        return "eval -i $this->transaction_id -d 1 -- ${b64encoded}";
    }

    /**
     * @param $conn
     * @param string $input
     */
    public function sendCommand(string $input)
    {
        if ($input === '') {
            return;
        }
        $command = $this->getCommand($input);
        stream_socket_sendto($this->conn, "${command}\0");
        $this->transaction_id++;
    }

    public function close()
    {
        fclose($this->conn);
    }
}
