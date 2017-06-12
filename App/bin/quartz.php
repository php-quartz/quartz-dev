#!/usr/bin/env php
<?php

use function Makasim\Values\register_cast_hooks;
use Quartz\App\Console\JobRunShellCommand;
use Quartz\App\Console\ManagementCommand;
use Quartz\App\Console\RemoteSchedulerProcessorCommand;
use Quartz\App\Console\SchedulerCommand;
use Quartz\App\SchedulerFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Yaml\Yaml;

// if you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

set_time_limit(0);

$dir = realpath(dirname($_SERVER['PHP_SELF']));
$loader = require $dir.'/../../vendor/autoload.php';

register_cast_hooks();

$configFile = $dir.'/../quartz.yml';
$config = [];
if (false !== $configContent = file_get_contents($configFile)) {
    $config = Yaml::parse($configContent);
}

$factory = new SchedulerFactory($config);

$application = new Application();
$application->add(new SchedulerCommand($factory));
$application->add(new JobRunShellCommand($factory));
$application->add(new ManagementCommand($factory));
$application->add(new RemoteSchedulerProcessorCommand($factory));

$application->run();
