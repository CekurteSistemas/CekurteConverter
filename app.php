#!/usr/bin/env php
<?php
// app/console

use Cekurte\FFMpegBundle\Command\ConverterCommand;
use Symfony\Component\Console\Application;
use Composer\Autoload\ClassLoader;

/**
 * @var $loader ClassLoader
 */
$loader = require __DIR__.'/vendor/autoload.php';

$application = new Application();
$application->add(new ConverterCommand);
$application->run();