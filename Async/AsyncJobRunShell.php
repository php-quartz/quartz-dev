<?php
namespace Quartz\Async;

use Enqueue\Client\Producer;
use Quartz\Core\JobExecutionContext;
use Quartz\Core\JobRunShell;

class AsyncJobRunShell implements JobRunShell
{
    /**
     * @var Producer
     */
    private $producer;

    public function execute(JobExecutionContext $context)
    {
        $this->producer->send('topic', ['' => $context->getTrigger()->get]);
    }
}
