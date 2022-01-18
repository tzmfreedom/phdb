#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

$options = getopt('d');
$debugger = new \PHPSimpleDebugger\Debugger(isset($options['d']));
$debugger->run(9003);
