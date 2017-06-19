<?php
namespace Quartz\App\Command;

use Quartz\Scheduler\StdScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ManagementCommand extends Command
{
    /**
     * @var StdScheduler
     */
    private $scheduler;

    /**
     * {@inheritdoc}
     */
    public function __construct(StdScheduler $scheduler)
    {
        parent::__construct('quartz:manage');

        $this->scheduler = $scheduler;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption('clear-all', null, InputOption::VALUE_NONE, 'Clears (deletes!) all scheduling data - all Jobs, Triggers, Calendars.')
            ->addOption('create-indexes', null, InputOption::VALUE_NONE, 'Creates all required storage indexes')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('clear-all')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('You are just about to delete all storage data. Are you sure? ', false, '/^(y|j)/i');

            if ($helper->ask($input, $output, $question)) {
                $this->scheduler->clear();
            }
        }

        if ($input->getOption('create-indexes')) {
            $output->writeln('Creating storage indexes');
            $this->scheduler->getStore()->createIndexes(); // TODO: is not part of interface :(
        }
    }
}
