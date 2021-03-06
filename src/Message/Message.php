<?php

namespace PHPSimpleDebugger\Message;

abstract class Message
{
    public abstract function isSkipped(): bool;

    public abstract function isStopping(): bool;

    /**
     * @param string $original
     */
    function __construct(public string $original)
    {
    }

    /**
     * @return string
     */
    public function getOriginalResponse(): string
    {
        return $this->original;
    }

    /**
     * @param string $message
     * @return Message|null
     */
    public static function createFromXML(string $message): ?Message
    {
        return match (self::getType($message)) {
            'init' => new InitMessage($message),
            'response' => new ResponseMessage($message),
            'stream' => new StreamMessage($message),
            default => null,
        };
    }

    /**
     * @param string $message
     * @return string
     */
    public static function getType(string $message): string
    {
        $dom = new \DOMDocument();
        $dom->loadXML($message);
        $rootTagName = $dom->firstElementChild->tagName;
        return match ($rootTagName) {
            'init' => 'init',
            'stream' => 'stream',
            'response' => 'response',
            default => '',
        };
    }
}
