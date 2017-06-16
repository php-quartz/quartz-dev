<?php
namespace Quartz\Bridge\Scheduler;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;
use Quartz\Scheduler\JobStore;
use Quartz\Scheduler\StdJobRunShell;

class JobRunShellProcessor implements PsrProcessor, CommandSubscriberInterface
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

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $data = JSON::decode($message->getBody());

        if (false == isset($data['fireInstanceId'])) {
            return Result::reject('fire instance id is empty');
        }

        if (false == $trigger = $this->store->retrieveFireTrigger($data['fireInstanceId'])) {
            return Result::reject(sprintf('There is not trigger with fire instance id: "%s"', $data['fireInstanceId']));
        }

        $this->runShell->execute($trigger);

        return Result::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedCommand()
    {
        return EnqueueJobRunShell::COMMAND;
    }
}
