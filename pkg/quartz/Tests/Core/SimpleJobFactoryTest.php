<?php
namespace Quartz\Tests\Core;

use PHPUnit\Framework\TestCase;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\JobFactory;
use Quartz\Core\SchedulerException;
use Quartz\Core\SimpleJobFactory;
use Quartz\JobDetail\JobDetail;

class SimpleJobFactoryTest extends TestCase
{
    public function testShouldImplementJobFactoryInterface()
    {
        $this->assertInstanceOf(JobFactory::class, new SimpleJobFactory());
    }

    public function testShouldReturnInstanceOfJobWhichSetInConstructor()
    {
        $job = $this->createMock(Job::class);

        $factory = new SimpleJobFactory([
            'job-name' => $job,
        ]);

        $jobDetail = new JobDetail();
        $jobDetail->setJobClass('job-name');

        $this->assertSame($job, $factory->newJob($jobDetail));
    }

    public function testShouldCreateAndReturnNewInstanceJobDetailsClass()
    {
        $factory = new SimpleJobFactory([]);

        $jobDetail = new JobDetail();
        $jobDetail->setJobClass(SimpleJobFactoryTestJob::class);

        $job = $factory->newJob($jobDetail);

        $this->assertInstanceOf(SimpleJobFactoryTestJob::class, $job);
    }

    public function testShouldThrowSchedulerExceptionIfClassWasNotFound()
    {
        $factory = new SimpleJobFactory([]);

        $jobDetail = new JobDetail();
        $jobDetail->setJobClass('ClassDoesNotExists');

        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Required instance of "Quartz\Core\Job", but got: "NULL"');

        $factory->newJob($jobDetail);
    }

    public function testShouldThrowSchedulerExceptionIfInstanceOfObjectIsNotJobInterface()
    {
        $factory = new SimpleJobFactory([
            'job-name' => new \stdClass(),
        ]);

        $jobDetail = new JobDetail();
        $jobDetail->setJobClass('job-name');

        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('Required instance of "Quartz\Core\Job", but got: "stdClass"');

        $factory->newJob($jobDetail);
    }
}

class SimpleJobFactoryTestJob implements Job
{
    public function execute(JobExecutionContext $context)
    {
    }
}
