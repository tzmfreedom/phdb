<?php

namespace PHPSimpleDebugger;

class Response
{
    function __construct(
        public string $type,
    ) {}

    /**
     * @param string $message
     * @return Response
     */
    public static function createFromXML(string $message): Response
    {
        $dom = new \DOMDocument();
        $dom->loadXML($message);
        $init = $dom->getElementsByTagName("init");
        if ($init->length != 0) {
            $obj = new self('init');
            foreach ($init[0]->attributes as $attribute) {
                $obj[$attribute->name] = $attribute->value;
            }
            return $obj;
        }
        $stream = $dom->getElementsByTagName('stream');
        if ($stream->length != 0) {
            $obj = new self('stream');
            foreach ($stream[0]->attributes as $attribute) {
                $obj[$attribute->name] = $attribute->value;
            }
            if (isset($obj['encoding']) && $obj['encoding'] === 'base64') {
                $obj['body'] = base64_decode($stream[0]->nodeValue);
            } else {
                $obj['body'] = $obj->nodeValue;
            }
            return $obj;
        }
        $response = $dom->getElementsByTagName("response");
        $obj = new self('response');
        foreach ($response[0]->attributes as $attribute) {
            $obj[$attribute->name] = $attribute->value;
        }
        if ($obj['command'] == 'eval') {
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
                $obj["property"] = $properties;
            }
        } else {
            $debugMessages = [];
            $messages = $dom->getElementsByTagName("message");
            if ($messages->length !== 0) {
                foreach ($messages[0]->attributes as $attribute) {
                    $debugMessages[$attribute->name] = $attribute->value;
                }
                $obj["xdebug:message"] = $debugMessages;
            }
        }
        return $obj;
    }
}
