<?php
use function Makasim\Values\register_cast_hooks;
use Quartz\App\SchedulerFactory;
use Quartz\Core\CalendarIntervalScheduleBuilder;
use Quartz\Core\DailyTimeIntervalScheduleBuilder;
use Quartz\Core\Job;
use Quartz\Core\JobBuilder;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\Scheduler;
use Quartz\Core\SimpleJobFactory;
use Quartz\Scheduler\StdJobRunShellFactory;
use Quartz\Core\TriggerBuilder;
use Quartz\Scheduler\Store\YadmStore;
use Quartz\Scheduler\Store\YadmStoreResource;;

$dir = realpath(dirname($_SERVER['PHP_SELF']));
$loader = require $dir.'/../vendor/autoload.php';

register_cast_hooks();

$config = [
    'uri' => sprintf('mongodb://%s:%s', getenv('MONGODB_HOST'), getenv('MONGODB_PORT')),
    'dbName' => getenv('MONGODB_DB')
];

class MyJob implements Job
{
    public function execute(JobExecutionContext $context)
    {
        echo sprintf('Now: %s | Scheduled: %s'.PHP_EOL, date('H:i:s'), $context->getTrigger()->getScheduledFireTime()->format('H:i:s'));
    }
}

$job = JobBuilder::newJob()
    ->ofType(DebugJob::class)
    ->build();

$trigger = TriggerBuilder::newTrigger()
    ->forJobDetail($job)
    ->endAt(new \DateTime('+1 day'))
    ->withSchedule(DailyTimeIntervalScheduleBuilder::dailyTimeIntervalSchedule()->withIntervalInSeconds(10))
    ->build();

$scheduler = (new SchedulerFactory())->getScheduler();
$scheduler->scheduleJob($trigger, $job);

//$scheduler = new Scheduler(new YadmStore(new YadmStoreResource($config)), new StdJobRunShellFactory(), new SimpleJobFactory());
//$scheduler->scheduleJob($trigger, $job);
