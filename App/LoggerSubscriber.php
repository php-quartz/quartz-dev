<?php
namespace Quartz\App;

use Psr\Log\LoggerInterface;
use Quartz\Events\Event;
use Quartz\Events\JobExecutionContextEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoggerSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function schedulerStarting()
    {
        $this->debug('Scheduler starting');
    }

    public function schedulerStarted()
    {
        $this->debug('Scheduler started');
    }

    public function jobToBeExecuted(JobExecutionContextEvent $event)
    {
        $this->debug(sprintf('Job to be executed: "%s"', (string) $event->getContext()->getJobDetail()->getKey()));
    }

    public function jobWasExecuted(JobExecutionContextEvent $event)
    {
        $this->debug(sprintf('Job was executed: "%s"', (string) $event->getContext()->getJobDetail()->getKey()));
    }

    public function jobExecutionVetoed(JobExecutionContextEvent $event)
    {
        $this->debug(sprintf('Job was executed: "%s"', (string) $event->getContext()->getJobDetail()->getKey()));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Event::SCHEDULER_STARTING => 'schedulerStarting',
            Event::SCHEDULER_STARTED => 'schedulerStarted',
            Event::JOB_TO_BE_EXECUTED => 'jobToBeExecuted',
            Event::JOB_WAS_EXECUTED => 'jobWasExecuted',
            Event::JOB_EXECUTION_VETOED => 'jobExecutionVetoed',
        ];
    }

    private function debug($message)
    {
        $this->logger->debug(sprintf('[%s] %s', date('H:i:s'), $message));
    }
}
