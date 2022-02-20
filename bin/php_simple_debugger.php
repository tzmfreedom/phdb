#!/usr/bin/env php
<?php

use PHPSimpleDebugger\Config;
use PHPSimpleDebugger\Debugger;

require_once 'vendor/autoload.php';

$options = getopt('dp:');
$port = !empty($options['p']) ? (int)$options['p'] : 9000;
$debug = isset($options['d']);

$config = new Config('./config.json');
$debugger = new Debugger($config, $debug);
$debugger->run($port);
