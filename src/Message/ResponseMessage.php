<?php

namespace PHPSimpleDebugger\Message;

use LucidFrame\Console\ConsoleTable;

class ResponseMessage extends Message
{
    public string $filename = '';
    public string $lineno = '';
    public string $status = '';
    public string $command = '';
    public string $transaction_id = '';
    public string $value = '';
    public array $stacks = [];
    /**
     * @var Property[]
     */
    public array $properties = [];

    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($message);
        $response = $dom->firstElementChild;
        $this->status = $response->getAttribute('status');
        $this->transaction_id = $response->getAttribute('transaction_id');
        $this->command = $response->getAttribute('command');

        switch ($this->command) {
            case 'eval':
            case 'context_get':
                foreach ($response->childNodes as $node) {
                    switch ($node->tagName) {
                        case 'property':
                            $property = $node;
                            $encoding = $property->getAttribute('encoding');
                            $type = $property->getAttribute('type');
                            $size = $property->getAttribute('size');
                            $fullname = $property->getAttribute('fullname');
                            $name = $property->getAttribute('name');
                            $facet = $property->getAttribute('facet');
                            $classname = $property->getAttribute('classname');
                            $value = $property->nodeValue;
                            if ($encoding === 'base64') {
                                $value = base64_decode($value);
                            }
                            if ($type === 'object' || $type === 'array') {
                                $subProperties = [];
                                foreach ($property->getElementsByTagName("property") as $subProperty) {
                                    $subValue = $subProperty->nodeValue;
                                    if ($subProperty->getAttribute('encoding') === 'base64') {
                                        $subValue = base64_decode($subValue);
                                    }
                                    $subProperties[] = new Property(
                                        $subProperty->getAttribute('name'),
                                        $subProperty->getAttribute('fullname'),
                                        $subProperty->getAttribute('type'),
                                        $subProperty->getAttribute('size'),
                                        $subProperty->getAttribute('facet'),
                                        $subValue,
                                        [],
                                        $subProperty->getAttribute('classname')
                                    );
                                }
                                $this->properties[] = new Property($name, $fullname, $type, $size, $facet, $value, $subProperties, $classname);
                            } else {
                                $this->properties[] = new Property($name, $fullname, $type, $size, $facet, $value, [], $classname);
                            }
                        break;
                        case 'message':
                        case 'error':
                            $this->value = $node->nodeValue;
                            break;
                    }
                }
                break;
            case 'run':
            case 'step_over':
            case 'step_into':
            case 'step_out':
                $messageTag = $response->firstElementChild;
                $this->lineno = $messageTag->getAttribute('lineno');
                $this->filename = $messageTag->getAttribute('filename');
                break;
            case 'stack_get':
                foreach ($response->childNodes as $stack) {
                    if ($stack->tagName !== 'stack') {
                        continue;
                    }
                    $this->stacks[] = new Stack(
                        $stack->getAttribute('where'),
                        $stack->getAttribute('level'),
                        $stack->getAttribute('filename'),
                        $stack->getAttribute('lineno'),
                    );
                }
                if (count($this->stacks) > 0) {
                    $this->lineno = $this->stacks[0]->lineno;
                    $this->filename = $this->stacks[0]->filename;
                }
                break;
        }
        parent::__construct($message);
    }

    /**
     * @return bool
     */
    public function isSkipped(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function format(): string
    {
        switch ($this->command) {
            case 'stack_get':
                return implode(PHP_EOL, array_map(function(Stack $stack) {
                    return "{$stack->filename}:{$stack->lineno}, {$stack->where}()";
                }, $this->stacks)) . PHP_EOL . PHP_EOL . $this->showFile();
            case 'eval':
                if ($this->value !== '') {
                    return $this->value;
                }
                $table = new ConsoleTable();
                $table
                    ->addHeader('type')
                    ->addHeader('value');
                foreach ($this->properties as $property) {
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
                foreach ($this->properties as $property) {
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
                return "$this->filename at $this->lineno\n" . $this->showFile();
        }
        return '';
    }

    /**
     * @return bool
     */
    public function isStopping(): bool
    {
        return $this->status === 'stopping';
    }

    /**
     * @return string
     */
    private function showFile(): string
    {
        $lineno = (int)$this->lineno;

        $fp = fopen($this->filename, 'r');
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
}
