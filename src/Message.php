<?php

namespace PHPSimpleDebugger;

class Message
{
    public string $command;
    public string $body;
    public string $status;
    public array $message;
    public array $property;
    public string $encoding;

    function __construct(
        public string $type,
    ) {}


    /**
     * @param string $message
     * @return InitMessage|StreamMessage|ResponseMessage|null
     */
    public static function createFromXML(string $message): InitMessage|StreamMessage|ResponseMessage|null
    {
        $dom = new \DOMDocument();
        $dom->loadXML($message);
        $init = $dom->getElementsByTagName("init");
        if ($init->length != 0) {
            return new InitMessage();
        }
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
            return new StreamMessage($type, $encoding, $stream[0]->nodeValue);
        }
        $response = $dom->getElementsByTagName("response");
        if ($response->length !== 0) {
            $status = '';
            $transaction_id = '';
            $command = '';
            foreach ($response[0]->attributes as $attribute) {
                switch ($attribute->name) {
                    case 'status':
                        $status = $attribute->value;
                        break;
                    case 'transaction_id':
                        $transaction_id = $attribute->value;
                        break;
                    case 'command':
                        $command = $attribute->value;
                        break;
                }
            }
            $message = new ResponseMessage($status, $command, $transaction_id);
            if ($message->command === 'eval') {
                $properties = [];
                $property = $dom->getElementsByTagName("property");
                if ($property->length != 0) {
                    foreach ($property[0]->attributes as $attribute) {
                        $properties[$attribute->name] = $attribute->value;
                    }
                    if (isset($properties['encoding']) && $properties['encoding'] === 'base64') {
                        $properties['body'] = base64_decode($property[0]->nodeValue);
                    } else if ($properties['type'] === 'object') {
                        $properties['body'] = "<".$properties['classname'].">";
                    } else {
                        $properties['body'] = $property[0]->nodeValue;
                    }
                    $message->property = $properties;
                }
                return $message;
            }
            $messages = $dom->getElementsByTagName("message");
            if ($messages->length !== 0) {
                $lineno = '';
                $filename = '';
                foreach ($messages[0]->attributes as $attribute) {
                    switch ($attribute->name) {
                        case 'lineno':
                            $lineno = $attribute->value;
                            break;
                        case 'filename':
                            $filename = $attribute->value;
                            break;
                    }
                }
                $message->lineno = $lineno;
                $message->filename = $filename;
            }
            return $message;
        }
        return null;
    }

    public function isStream(): bool
    {
        return $this->type === 'stream';
    }
}
