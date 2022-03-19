<?php

namespace PHPSimpleDebugger\Message;

class Breakpoint
{
    /**
     * @param string $id
     * @param string $filename
     * @param string $lineno
     */
    public function __construct(
        public string $id,
        public string $filename,
        public string $lineno,
    ){}
}
