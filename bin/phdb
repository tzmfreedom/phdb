#!/usr/bin/env php
<?php

use PHPSimpleDebugger\Config;
use PHPSimpleDebugger\Debugger;

require_once 'vendor/autoload.php';

$options = getopt('dp:c:');
$port = isset($options['p']) ? (int)$options['p'] : 9000;
$debug = isset($options['d']);
$configFile = $options['c'] ?? '';

$config = new Config($configFile);
$debugger = new Debugger($config, $debug);
$debugger->run($port);
