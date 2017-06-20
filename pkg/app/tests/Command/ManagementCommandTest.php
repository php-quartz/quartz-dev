<?php
namespace Quartz\App\Tests\Command;

use PHPUnit\Framework\TestCase;
use Quartz\App\Command\ManagementCommand;
use Quartz\Scheduler\StdScheduler;
use Quartz\Scheduler\Store\YadmStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class ManagementCommandTest extends TestCase
{
    public function testShouldExtendSymfonyCommand()
    {
        $this->assertInstanceOf(Command::class, new ManagementCommand($this->createSchedulerMock()));
    }

    public function testShouldClearAll()
    {
        $scheduler = $this->createSchedulerMock();
        $scheduler
            ->expects($this->once())
            ->method('clear')
        ;

        $command = new ManagementCommand($scheduler);
        $command->setHelperSet(new HelperSet(['question' => new QuestionHelper()]));

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);
        $tester->execute([
            '--clear-all' => true,
        ]);

        $this->assertContains('You are just about to delete all storage data. Are you sure', $tester->getDisplay());
    }

    public function testShouldCreateStoreIndexes()
    {
        $store = $this->createStoreMock();
        $store
            ->expects($this->once())
            ->method('createIndexes')
        ;

        $scheduler = $this->createSchedulerMock();
        $scheduler
            ->expects($this->once())
            ->method('getStore')
            ->willReturn($store)
        ;

        $command = new ManagementCommand($scheduler);

        $tester = new CommandTester($command);
        $tester->execute([
            '--create-indexes' => true,
        ]);

        $this->assertContains('Creating storage indexes', $tester->getDisplay());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|StdScheduler
     */
    private function createSchedulerMock()
    {
        return $this->createMock(StdScheduler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|YadmStore
     */
    private function createStoreMock()
    {
        return $this->createMock(YadmStore::class);
    }
}
