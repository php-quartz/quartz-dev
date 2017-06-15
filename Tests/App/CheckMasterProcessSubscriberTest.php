<?php
namespace Quartz\Tests\App;

use PHPUnit\Framework\TestCase;
use Quartz\App\CheckMasterProcessSubscriber;
use Quartz\Core\SchedulerException;
use Quartz\Events\Event;
use Quartz\Events\TickEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckMasterProcessSubscriberTest extends TestCase
{
    public function testShouldImplementEventSubscriberInterface()
    {
        $this->assertInstanceOf(EventSubscriberInterface::class, new CheckMasterProcessSubscriber());
    }

    public function testShouldReturnExpectedSubscribedEvents()
    {
        $expectedEvents = [
            Event::SCHEDULER_TICK => 'checkMasterProcessor',
        ];

        $this->assertSame($expectedEvents, CheckMasterProcessSubscriber::getSubscribedEvents());
    }

    public function testShouldThrowExceptionIfMasterProcessPidEnvIsNotSet()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('The extension rely on MASTER_PROCESS_PID env var set but it is not set.');

        $s = new CheckMasterProcessSubscriber();
        $s->checkMasterProcessor(new TickEvent());
    }

    public function testShouldSetInterruptedIfMasterProcessIsNotRunning()
    {
        putenv('MASTER_PROCESS_PID=-12345');

        $s = new CheckMasterProcessSubscriber();
        $s->checkMasterProcessor($event = new TickEvent());

        $this->assertTrue($event->isInterrupted());
    }

    public function testShouldNotSetInterruptedIfMasterProcessIsRunning()
    {
        putenv('MASTER_PROCESS_PID='.posix_getpid());

        $s = new CheckMasterProcessSubscriber();
        $s->checkMasterProcessor($event = new TickEvent());

        $this->assertFalse($event->isInterrupted());
    }
}
