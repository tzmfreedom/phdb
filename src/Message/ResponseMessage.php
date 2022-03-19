<?php

namespace PHPSimpleDebugger\Message;

class ResponseMessage extends Message
{
    public string $filename = '';
    public string $lineno = '';
    public string $status = '';
    public string $command = '';
    public string $transaction_id = '';
    public string $value = '';
    /**
     * @var Stack[]
     */
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
            case 'source':
                $this->value = base64_decode($response->nodeValue);
                break;
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
                if ($this->isStopping()) {
                    break;
                }
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
     * @return bool
     */
    public function isStopping(): bool
    {
        return $this->status === 'stopping';
    }
}
