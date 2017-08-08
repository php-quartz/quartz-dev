<?php

namespace Quartz\App\Command;

use Quartz\Bridge\Enqueue\AmqpQuartzDelayStrategy;
use Quartz\Core\Scheduler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendDelayedMessageCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('send:delayed');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $delayStrategy = new AmqpQuartzDelayStrategy($this->getScheduler());

        $context = $this->getContainer()->get('enqueue.transport.context');
        $context->setDelayStrategy($delayStrategy);

        $queue = $context->createQueue('delay-queue');
        $context->declareQueue($queue);

        $message = $context->createMessage('the body');

        $producer = $context->createProducer();
        $producer->setDeliveryDelay(60000);

        $producer->send($queue, $message);
    }

    /**
     * @return Scheduler
     */
    private function getScheduler()
    {
        return $this->getContainer()->get('quartz.remote.scheduler');
    }
}
