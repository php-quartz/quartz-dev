<?php
namespace Quartz\App\Console;

use Quartz\App\SchedulerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ManagementCommand extends Command
{
    /**
     * @var SchedulerFactory
     */
    private $factory;

    /**
     * {@inheritdoc}
     */
    public function __construct(SchedulerFactory $factory)
    {
        parent::__construct('manage');

        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('clear-all', null, InputOption::VALUE_NONE, 'Clears (deletes!) all scheduling data - all Jobs, Triggers, Calendars.')
            ->addOption('create-indexes', null, InputOption::VALUE_NONE, 'Creates all required storage indexes')
            ->addOption('create-queues', null, InputOption::VALUE_NONE, 'Creates all required queues')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scheduler = $this->factory->getScheduler();

        if ($input->getOption('clear-all')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('You are just about to delete all storage data. Are you sure? ', false, '/^(y|j)/i');

            if ($helper->ask($input, $output, $question)) {
                $scheduler->clear();
            }
        }

        if ($input->getOption('create-indexes')) {
            $output->writeln('Creating storage indexes');
            $scheduler->getStore()->createIndexes(); // TODO: is not part of interface :(
        }

        if ($input->getOption('create-queues')) {
            $output->writeln('Creating enqueue queues');
            $this->factory->getEnqueue()->setupBroker();
        }
    }
}
