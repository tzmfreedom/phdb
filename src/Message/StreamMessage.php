<?php

namespace PHPSimpleDebugger\Message;

class StreamMessage extends Message
{
    private string $type;
    private string $encoding;
    private string $body;

    /**
     * @param string $message
     */
    function __construct(public string $message)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($message);
        $stream = $dom->getElementsByTagName('stream');
        if ($stream->length !== 0) {
            $type = '';
            $encoding = '';
            foreach ($stream[0]->attributes as $attribute) {
                switch ($attribute->name) {
                    case 'type':
                        $type = $attribute->value;
                        break;
                    case 'encoding':
                        $encoding = $attribute->value;
                        break;
                }
            }
            $this->type = $type;
            $this->encoding = $encoding;
            $this->body = $stream[0]->nodeValue;
        }
        parent::__construct($message);
    }

    /**
     * @return bool
     */
    public function isSkipped(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isStopping(): bool
    {
        return false;
    }
}
