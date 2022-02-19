<?php

namespace PHPSimpleDebugger;

class ResponseMessage
{
    public array $property;
    public string $filename;
    public string $lineno;

    public function __construct(
        public string $status,
        public string $command,
        public string $transaction_id
    )
    {}
}
