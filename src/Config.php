<?php

namespace PHPSimpleDebugger;

class Config
{
    /**
     * @var string[]
     */
    public array $initCommands = [];

    /**
     * @param string $file
     */
    public function __construct(string $file)
    {
        if ($file === '') {
            $this->initCommands[] = 'continue';
            return;
        }
        $content = file_get_contents($file);
        if ($content !== false) {
            $config = json_decode($content, true);
            foreach ($config['breakpoints'] as $breakpoint) {
                $filename = $breakpoint['file'];
                $lineno = $breakpoint['lineno'];
                $this->initCommands[] = "breakpoint_set $filename $lineno";
            }
            $this->initCommands[] = 'continue';
        }
    }
}
