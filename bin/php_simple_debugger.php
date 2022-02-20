#!/usr/bin/env php
<?php

use PHPSimpleDebugger\Config;
use PHPSimpleDebugger\Debugger;

require_once 'vendor/autoload.php';

$options = getopt('d');
$config = new Config('./config.json');
$debugger = new Debugger($config, isset($options['d']));
$debugger->run(9003);
