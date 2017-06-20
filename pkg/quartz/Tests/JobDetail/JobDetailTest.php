<?php
namespace Quartz\Tests\JobDetail;

use PHPUnit\Framework\TestCase;
use Quartz\Core\JobDetail as JobDetailInterface;
use Quartz\Core\Key;
use Quartz\JobDetail\JobDetail;

class JobDetailTest extends TestCase
{
    public function testShouldImplementJobDetailInterface()
    {
        $this->assertInstanceOf(JobDetailInterface::class, new JobDetail());
    }

    public function testCouldGetSetKey()
    {
        $job = new JobDetail();
        $job->setKey($key = new Key('name', 'group'));

        $this->assertTrue($key->equals($job->getKey()));
    }

    public function testCouldGetSetDescription()
    {
        $job = new JobDetail();
        $job->setDescription('the description');

        $this->assertSame('the description', $job->getDescription());
    }

    public function testCouldGetSetDurable()
    {
        $job = new JobDetail();

        $job->setDurable(true);
        $this->assertTrue($job->isDurable());

        $job->setDurable(false);
        $this->assertFalse($job->isDurable());
    }

    public function testCouldGetSetJobClass()
    {
        $job = new JobDetail();
        $job->setJobClass('the job class');

        $this->assertSame('the job class', $job->getJobClass());
    }

    public function testCouldGetSetJobDataMap()
    {
        $job = new JobDetail();
        $job->setJobDataMap(['key' => 'value']);

        $this->assertSame(['key' => 'value'], $job->getJobDataMap());
    }
}
