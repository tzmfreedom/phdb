<?php

namespace PHPSimpleDebugger;

class StreamMessage
{
    public string $body;
    public string $type;
    public string $encoding;

    function __construct(string $type, string $encoding, string $body)
    {
        $this->type = $type;
        $this->encoding = $encoding;
        if ($this->encoding === 'base64') {
            $this->body = base64_decode($body);
        } else {
            $this->body = $body;
        }
    }
}
