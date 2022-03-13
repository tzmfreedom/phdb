<?php

namespace PHPSimpleDebugger\Message;

class InitMessage extends Message
{
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
        return false;
    }
}
