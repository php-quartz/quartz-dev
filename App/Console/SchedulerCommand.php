<?php
namespace Quartz\App\Console;

use Quartz\App\LoggerSubscriber;
use Quartz\App\SchedulerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerCommand extends Command
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
        parent::__construct('scheduler');

        $this->factory = $factory;
    }

    protected function configure()
    {
        $this->addOption('setup', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('setup')) {
            $this->factory->getEnqueue()->setupBroker();
            // TODO: create store index
        }

        $scheduler = $this->factory->getScheduler();
        $logger = new LoggerSubscriber(new ConsoleLogger($output));
        $scheduler->getEventDispatcher()->addSubscriber($logger);

        $scheduler->start();
    }
}
