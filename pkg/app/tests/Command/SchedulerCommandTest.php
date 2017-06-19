<?php
namespace Quartz\App\Tests\Command;

use PHPUnit\Framework\TestCase;
use Quartz\App\Command\SchedulerCommand;
use Quartz\Bridge\LoggerSubscriber;
use Quartz\Bridge\SignalSubscriber;
use Quartz\Scheduler\StdScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SchedulerCommandTest extends TestCase
{
    public function testShouldExtendSymfonyCommand()
    {
        $this->assertInstanceOf(Command::class, new SchedulerCommand($this->createSchedulerMock()));
    }

    public function testShouldStartScheduler()
    {
        $dispatcher = $this->createEventDispatcherMock();

        $scheduler = $this->createSchedulerMock();
        $scheduler
            ->expects($this->any())
            ->method('getEventDispatcher')
            ->willReturn($dispatcher)
        ;
        $scheduler
            ->expects($this->once())
            ->method('start')
        ;

        $command = new SchedulerCommand($scheduler);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEmpty($tester->getDisplay());
    }

    public function testShouldAddLoggerSubscriber()
    {
        $dispatcher = $this->createEventDispatcherMock();
        $dispatcher
            ->expects($this->at(0))
            ->method('addSubscriber')
            ->with($this->isInstanceOf(LoggerSubscriber::class))
        ;

        $scheduler = $this->createSchedulerMock();
        $scheduler
            ->expects($this->any())
            ->method('getEventDispatcher')
            ->willReturn($dispatcher)
        ;

        $command = new SchedulerCommand($scheduler);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEmpty($tester->getDisplay());
    }

    public function testShouldAddSignalSubscriber()
    {
        $dispatcher = $this->createEventDispatcherMock();
        $dispatcher
            ->expects($this->at(1))
            ->method('addSubscriber')
            ->with($this->isInstanceOf(SignalSubscriber::class))
        ;

        $scheduler = $this->createSchedulerMock();
        $scheduler
            ->expects($this->any())
            ->method('getEventDispatcher')
            ->willReturn($dispatcher)
        ;

        $command = new SchedulerCommand($scheduler);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertEmpty($tester->getDisplay());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|StdScheduler
     */
    private function createSchedulerMock()
    {
        return $this->createMock(StdScheduler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    private function createEventDispatcherMock()
    {
        return $this->createMock(EventDispatcherInterface::class);
    }
}
