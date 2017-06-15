<?php
namespace Quartz\App;

use Quartz\Core\SchedulerException;
use Quartz\Events\Event;
use Quartz\Events\TickEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckMasterProcessSubscriber implements EventSubscriberInterface
{
    /**
     * @param TickEvent $event
     *
     * @throws SchedulerException
     */
    public function checkMasterProcessor(TickEvent $event)
    {
        if (false == $mPid = getenv('MASTER_PROCESS_PID')) {
            throw new SchedulerException('The extension rely on MASTER_PROCESS_PID env var set but it is not set.');
        }

        if (false == posix_kill($mPid, 0)) {
            $event->setInterrupted(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Event::SCHEDULER_TICK => 'checkMasterProcessor',
        ];
    }
}
