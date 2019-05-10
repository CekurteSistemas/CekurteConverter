#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

use Cercal\IO\MediaOrganizer\Command;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new Command\Converter());
$application->add(new Command\Organizer());
$application->run();
