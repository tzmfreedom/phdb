<?php

namespace PHPSimpleDebugger;

use LucidFrame\Console\ConsoleTable;
use PHPSimpleDebugger\Message\InitMessage;
use PHPSimpleDebugger\Message\Message;
use PHPSimpleDebugger\Message\ResponseMessage;
use PHPSimpleDebugger\Message\StreamMessage;

class ConsoleView
{
    public function __construct(public Config $config)
    {}

    /**
     * @param Message $message
     */
    public function render(Message $message)
    {
        echo $this->renderAsString($message);
    }

    /**
     * @param Message $message
     * @return string
     */
    public function renderAsString(Message $message): string
    {
        if ($message::class === InitMessage::class) {
            return '';
        }
        if ($message::class === StreamMessage::class) {
            if ($message->encoding === 'base64') {
                return base64_decode($message->body);
            }
            return $message->body;
        }
        switch ($message->command) {
            case 'stack_get':
                return implode(PHP_EOL, array_map(function(Stack $stack) {
                        $filename = $this->getFilename($stack->filename);
                        return "{$filename}:{$stack->lineno}, {$stack->where}()";
                    }, $message->stacks)) . PHP_EOL . PHP_EOL . $this->showFile($message);
            case 'eval':
                if ($message->value !== '') {
                    return $message->value;
                }
                $table = new ConsoleTable();
                $table
                    ->addHeader('type')
                    ->addHeader('value');
                foreach ($message->properties as $property) {
                    if ($property->type === 'object' || $property->type === 'array') {
                        $body = implode(', ', array_map(function(Property $property) {
                            $value = preg_replace("/\e/", '', $property->value);
                            return "$property->name => $value";
                        }, $property->properties));
                        if (mb_strlen($body) > 100) {
                            $body = mb_substr($body, 0, 100) . '...';
                        }
                        $table->addRow([$property->classname, $body]);
                    } else {
                        $size = '';
                        if ($property->size !== '') {
                            $size = "({$property->size})";
                        }
                        $table->addRow(["{$property->type}$size", $property->value]);
                    }
                }
                return $table->getTable();
            case 'context_get':
                $table = new ConsoleTable();
                $table
                    ->addHeader('name')
                    ->addHeader('type')
                    ->addHeader('value');
                foreach ($message->properties as $property) {
                    if ($property->type === 'object' || $property->type === 'array') {
                        $body = implode(PHP_EOL, array_map(function(Property $property) {
                            $value = preg_replace("/\e/", '', $property->value);
                            return "$property->name => $value";
                        }, $property->properties));
                        if (mb_strlen($body) > 100) {
                            $body = mb_substr($body, 0, 100) . '...';
                        }
                        $table->addRow([$property->name, $property->classname, $body]);
                    } else {
                        $size = '';
                        if ($property->size !== '') {
                            $size = "({$property->size})";
                        }
                        $table->addRow([$property->name, "{$property->type}$size", $property->value]);
                    }
                }
                return $table->getTable();
            case 'run':
            case 'step_over':
            case 'step_into':
            case 'step_out':
                $filename= $this->getFilename($message->filename);
                return "$filename at $message->lineno\n" . $this->showFile($message);
        }
        return '';
    }


    /**
     * @param ResponseMessage $message
     * @return string
     */
    private function showFile(ResponseMessage $message): string
    {
        $lineno = (int)$message->lineno;

        $filename = $this->getFilename($message->filename);
        $fp = fopen($filename, 'r');
        $size = 15;
        $current = 0;
        $cnt = $lineno < 8 ? 0 : $lineno - 8;
        for ($i = 0; $i < $cnt; $i++) {
            fgets($fp);
            $current++;
        }
        $max = $lineno + 7;
        $width = strlen((string)$max);
        $lines = [];
        for ($i = 0; $i < $size; $i++) {
            $line = fgets($fp);
            $current++;
            if ($line) {
                $formatLine = sprintf("%0{$width}d", $current);
                if ($current === $lineno) {
                    $lines[] = ">> $formatLine: $line";
                } else {
                    $lines[] = "   $formatLine: $line";
                }
            }
        }
        fclose($fp);
        return implode('', $lines);
    }

    /**
     * @param string $filename
     * @return array|string|string[]
     */
    private function getFilename(string $filename)
    {
        $remote = $this->config->fileMapping['remote'];
        $local = $this->config->fileMapping['local'];
        if (isset($local) && isset($remote)) {
            return str_replace($remote, $local, $filename);
        }
        return $filename;
    }
}

