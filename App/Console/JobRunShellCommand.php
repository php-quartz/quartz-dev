<?php
namespace Quartz\App\Console;

use Enqueue\Consumption\Extension\LoggerExtension;
use Quartz\App\LoggerSubscriber;
use Quartz\App\SchedulerFactory;
use Quartz\App\Async\AsyncJobRunShell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class JobRunShellCommand extends Command
{
    /**
     * SchedulerFactory
     */
    private $factory;

    /**
     * @param SchedulerFactory $factory
     */
    public function __construct(SchedulerFactory $factory)
    {
        parent::__construct('job-run-shell');

        $this->factory = $factory;
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $enqueue = $this->factory->getEnqueue();
        $processor = $this->factory->getJobRunShellProcessor();

        $scheduler = $this->factory->getScheduler();
        $logger = new LoggerSubscriber(new ConsoleLogger($output));
        $scheduler->getEventDispatcher()->addSubscriber($logger);

        $enqueue->bind(AsyncJobRunShell::TOPIC, AsyncJobRunShell::TOPIC, function($message, $context) use ($processor) {
            return $processor->process($message, $context);
        });

        $enqueue->consume(new LoggerExtension(new ConsoleLogger($output)));
    }
}
