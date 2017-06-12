<?php
namespace Quartz\App\Console;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\Extension\ReplyExtension;
use Quartz\App\LoggerSubscriber;
use Quartz\App\RemoteScheduler;
use Quartz\App\SchedulerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class RemoteSchedulerProcessorCommand extends Command
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
        parent::__construct('remote-scheduler-processor');

        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $enqueue = $this->factory->getEnqueue();
        $processor = $this->factory->getRemoteSchedulerProcessor();

        $scheduler = $this->factory->getScheduler();
        $logger = new LoggerSubscriber(new ConsoleLogger($output));
        $scheduler->getEventDispatcher()->addSubscriber($logger);

        $enqueue->bind(RemoteScheduler::TOPIC, RemoteScheduler::TOPIC, function($message, $context) use ($processor) {
            return $processor->process($message, $context);
        });

        $extensions = new ChainExtension([new ReplyExtension(), new LoggerExtension(new ConsoleLogger($output))]);

        $enqueue->consume($extensions);
    }
}
