<?php
namespace Quartz\Bridge\Scheduler;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\QueueSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Quartz\Scheduler\JobStore;
use Quartz\Scheduler\StdJobRunShell;

class JobRunShellProcessor implements Processor, CommandSubscriberInterface, QueueSubscriberInterface
{
    /**
     * @var JobStore
     */
    private $store;

    /**
     * @var StdJobRunShell
     */
    private $runShell;

    /**
     * @param JobStore       $store
     * @param StdJobRunShell $runShell
     */
    public function __construct(JobStore $store, StdJobRunShell $runShell)
    {
        $this->store = $store;
        $this->runShell = $runShell;
    }

    public function process(Message $message, Context $context): Result
    {
        $data = JSON::decode($message->getBody());

        if (false == isset($data['fireInstanceId'])) {
            return Result::reject('fire instance id is empty');
        }

        if (false == $trigger = $this->store->retrieveFireTrigger($data['fireInstanceId'])) {
            return Result::reject(sprintf('There is not trigger with fire instance id: "%s"', $data['fireInstanceId']));
        }

        $this->runShell->execute($trigger);

        return Result::ack();
    }

    public static function getSubscribedCommand(): array
    {
        return [
            'command' => EnqueueJobRunShell::COMMAND,
            'queue' => EnqueueJobRunShell::COMMAND,
            'prefix_queue' => false,
            'exclusive' => true,
        ];
    }

    public static function getSubscribedQueues(): array
    {
        return [EnqueueJobRunShell::COMMAND];
    }
}
