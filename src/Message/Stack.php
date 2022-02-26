<?php

namespace PHPSimpleDebugger\Message;

class Stack
{
    /**
     * @param string $where
     * @param string $level
     * @param string $filename
     * @param string $lineno
     */
    public function __construct(
        public string $where,
        public string $level,
        public string $filename,
        public string $lineno,
    ){}
}
