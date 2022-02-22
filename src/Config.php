<?php

namespace PHPSimpleDebugger;

class Config
{
    /**
     * @var array<string>
     */
    public array $initCommands = [];

    public bool $autoStart = true;

    /**
     * @param string $file
     */
    public function __construct(string $file)
    {
        $content = file_get_contents($file);
        $config = json_decode($content, true);
        foreach ($config['breakpoints'] as $breakpoint) {
            $filename = $breakpoint['file'];
            $lineno = $breakpoint['lineno'];
            $this->initCommands[] = "breakpoint_set $filename $lineno";
        }
        if ($config['autoStart'] === false) {
            $this->autoStart = false;
        }
    }
}
