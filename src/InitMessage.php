<?php

namespace PHPSimpleDebugger;

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
     * @return string
     */
    public function format(): string
    {
        return '';
    }

    /**
     * @return bool
     */
    public function isStopping(): bool
    {
        return false;
    }
}
