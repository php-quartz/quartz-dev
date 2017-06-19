<?php
namespace Quartz\App\Command;

use Quartz\Bridge\LoggerSubscriber;
use Quartz\Bridge\SignalSubscriber;
use Quartz\Scheduler\StdScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerCommand extends Command
{
    /**
     * @var StdScheduler
     */
    private $scheduler;

    public function __construct(StdScheduler $scheduler)
    {
        parent::__construct('quartz:scheduler');

        $this->scheduler = $scheduler;
        $this->setDescription('Quartz scheduler');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->scheduler->getEventDispatcher()->addSubscriber(new LoggerSubscriber(new ConsoleLogger($output)));
        $this->scheduler->getEventDispatcher()->addSubscriber(new SignalSubscriber());

        $this->scheduler->start();
    }
}
