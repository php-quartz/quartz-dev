<?php

use function Makasim\Values\register_cast_hooks;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\SimpleJobFactory;
use Quartz\Scheduler\StdJobRunShell;
use Quartz\Scheduler\StdJobRunShellFactory;
use Quartz\Scheduler\StdScheduler;
use Quartz\Scheduler\Store\YadmStore;
use Quartz\Scheduler\Store\YadmStoreResource;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once '../vendor/autoload.php';

register_cast_hooks();

$config = [
    'uri' => sprintf('mongodb://%s:%s', getenv('MONGODB_HOST'), getenv('MONGODB_PORT')),
    'dbName' => getenv('MONGODB_DB')
];

$store = new YadmStore(new YadmStoreResource($config));
$store->clearAllSchedulingData();

class MyJob implements Job
{
    public function execute(JobExecutionContext $context)
    {
        echo sprintf('Now: %s | Scheduled: %s'.PHP_EOL, date('H:i:s'), $context->getTrigger()->getScheduledFireTime()->format('H:i:s'));
    }
}

$scheduler = new StdScheduler($store, new StdJobRunShellFactory(new StdJobRunShell()), new SimpleJobFactory(), new EventDispatcher());
$scheduler->start();
