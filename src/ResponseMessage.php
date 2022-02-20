<?php

namespace PHPSimpleDebugger;

class ResponseMessage extends Message
{
    public array $property;
    public string $filename;
    public string $lineno;
    public string $status = '';
    public string $command = '';
    public string $transaction_id = '';
    public string $body = '';

    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($message);
        $responses = $dom->getElementsByTagName('response');
        if ($responses->length !== 0) {
            foreach ($responses[0]->attributes as $attribute) {
                switch ($attribute->name) {
                    case 'status':
                        $this->status = $attribute->value;
                        break;
                    case 'transaction_id':
                        $this->transaction_id = $attribute->value;
                        break;
                    case 'command':
                        $this->command = $attribute->value;
                        break;
                }
            }
            if ($this->command === 'eval') {
                $properties = [];
                $property = $dom->getElementsByTagName("property");
                if ($property->length != 0) {
                    foreach ($property[0]->attributes as $attribute) {
                        $properties[$attribute->name] = $attribute->value;
                    }
                    if (isset($properties['encoding']) && $properties['encoding'] === 'base64') {
                        $properties['body'] = base64_decode($property[0]->nodeValue);
                    } else if ($properties['type'] === 'object') {
                        $properties['body'] = "<" . $properties['classname'] . ">";
                    } else {
                        $properties['body'] = $property[0]->nodeValue;
                    }
                    $this->property = $properties;
                }
                $messages = $dom->getElementsByTagName("message");
                if ($messages->length !== 0) {
                    $this->body = $messages[0]->nodeValue;
                }
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
                $this->lineno = $lineno;
                $this->filename = $filename;
            }
        }
        parent::__construct($message);
    }

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
        if (isset($this->property)) {
            return var_export($this->property, true);
        }
        if (!empty($this->lineno) && !empty($this->filename)) {
            return "$this->filename at $this->lineno\n" . $this->showFile();
        }
        if (isset($this->body)) {
            return $this->body;
        }
        return '';
    }

    /**
     * @return bool
     */
    public function isStopping(): bool
    {
        return $this->status === 'stopping';
    }

    /**
     * @return string
     */
    private function showFile(): string
    {
        $lineno = (int)$this->lineno;

        $fp = fopen($this->filename, 'r');
        $size = 15;
        $current = 0;
        $cnt = $lineno < 8 ? 0 : $lineno - 8;
        for ($i = 0; $i < $cnt; $i++) {
            fgets($fp);
            $current++;
        }
        $max = $lineno + 7;
        $width = strlen((string)$max);
        $lines = [];
        for ($i = 0; $i < $size; $i++) {
            $line = fgets($fp);
            $current++;
            if ($line) {
                $formatLine = sprintf("%0{$width}d", $current);
                if ($current === $lineno) {
                    $lines[] = ">> $formatLine: $line";
                } else {
                    $lines[] = "   $formatLine: $line";
                }
            }
        }
        fclose($fp);
        return implode('', $lines);
    }
}
