<?php
namespace Quartz\Bridge;

use Quartz\Core\SchedulerException;
use Quartz\Events\Event;
use Quartz\Events\TickEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SignalSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $interruptConsumption;

    public function __construct()
    {
        $this->interruptConsumption = false;
    }

    public function registerHandleSignalCallback()
    {
        if (false == extension_loaded('pcntl')) {
            throw new SchedulerException('The pcntl extension is required in order to catch signals.');
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        pcntl_signal(SIGQUIT, [$this, 'handleSignal']);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);
    }

    public function handleSignalDispatch(TickEvent $event)
    {
        if ($this->interruptConsumption) {
            $event->setInterrupted(true);
        }
    }

    /**
     * @param int $signal
     */
    public function handleSignal($signal)
    {
        switch ($signal) {
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
            case SIGINT:   // 2  : ctrl+c
                $this->interruptConsumption = true;
                break;
            default:
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Event::SCHEDULER_STARTING => 'registerHandleSignalCallback',
            Event::SCHEDULER_TICK => 'handleSignalDispatch',
        ];
    }
}
