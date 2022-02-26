<?php

namespace PHPSimpleDebugger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPSimpleDebugger\Message\Message;

class Debugger
{
    private Logger $logger;

    /**
     * @param Config $config
     * @param bool $debug
     */
    function __construct(public Config $config, bool $debug = false)
    {
        $level = $debug ? Logger::DEBUG : Logger::INFO;
        $handler = (new StreamHandler('php://stdout', $level))
            ->setFormatter(new LineFormatter('[%datetime%] %level_name%: %message%' . PHP_EOL));
        $logger = (new Logger('debugger'))->pushHandler($handler);
        $this->logger = $logger;
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

        while ($res = stream_socket_accept($socket, -1)) {
            $conn = new Connection($res);
            $this->handleConnection($conn);
        }
        fclose($socket);
    }

    /**
     * @param Connection $conn
     */
    private function handleConnection(Connection $conn)
    {
        try {
            // handle init
            $this->handleMessages($conn);

            foreach ($this->config->initCommands as $command) {
                if ($this->sendCommand($conn, $command)) {
                    $this->handleMessages($conn);
                }
            }

            while(true) {
                $input = readline(">> ");
                if ($input === "exit") {
                    throw new StoppingException();
                }
                if (in_array($input, ['h', "help"], true)) {
                    echo 'next' .PHP_EOL;
                    echo 'continue' .PHP_EOL;
                    echo 'step' .PHP_EOL;
                    echo 'status' .PHP_EOL;
                    echo 'source' .PHP_EOL;
                    echo 'whereami' .PHP_EOL;
                    echo 'breakpoint_set' .PHP_EOL;
                    echo 'local' .PHP_EOL;
                    echo 'super_global' .PHP_EOL;
                    echo 'constants' .PHP_EOL;
                    continue;
                }
                if ($this->sendCommand($conn, $input)) {
                    $this->handleMessages($conn);
                }
            }
        } catch (StoppingException $e) {
            // pass
        } finally {
            $conn->close();
        }
    }

    /**
     * @param Message $message
     * @throws StoppingException
     */
    private function handleMessage(Message $message)
    {
        if ($message->isStopping()) {
            throw new StoppingException();
        }
        $output = $message->format();
        if (!empty($output)) {
            echo $output . PHP_EOL;
        }
    }

    /**
     * @param Connection $conn
     * @param string $input
     * @return bool
     */
    private function sendCommand(Connection $conn, string $input): bool
    {
        $command = $conn->getCommand($input);
        $this->logger->debug((string)$command);
        return $conn->sendCommand($command);
    }

    /**
     * @param Connection $conn
     * @throws StoppingException
     */
    private function handleMessages(Connection $conn)
    {
        $messages = $conn->getMessages();
        $this->logger->debug(implode("\n", array_map(function(Message $message): string {
            return $message->getOriginalResponse();
        }, $messages)));
        foreach ($messages as $message) {
            $this->handleMessage($message);
        }
    }
}

