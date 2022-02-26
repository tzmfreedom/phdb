<?php

namespace PHPSimpleDebugger\Message;

class Property
{
    /**
     * @param string $name
     * @param string $fullname
     * @param string $type
     * @param string $size
     * @param string $facet
     * @param string $value
     * @param Property[] $properties
     * @param string $classname
     */
    public function __construct(
        public string $name,
        public string $fullname,
        public string $type,
        public string $size,
        public string $facet,
        public string $value,
        public array $properties,
        public string $classname,
    )
    {}
}
