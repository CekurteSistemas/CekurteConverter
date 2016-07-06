#!/usr/bin/env php
<?php
// app/console

use Cekurte\Media\Organizer\Command\Converter;
use Cekurte\Media\Organizer\Command\Organizer;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Console\Application;

/**
 * @var $loader ClassLoader
 */
$loader = require __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$application = new Application();
$application->add(new Converter);
$application->add(new Organizer);
$application->run();
