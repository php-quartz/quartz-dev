<?php
namespace Quartz\App;

use Enqueue\Client\RpcClient;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;

class EnqueueResponseJob implements Job
{
    /**
     * @var RpcClient
     */
    private $rpcClient;

    /**
     * @var int msec
     */
    private $timeout;

    /**
     * @param RpcClient $rpcClient
     */
    public function __construct(RpcClient $rpcClient)
    {
        $this->rpcClient = $rpcClient;
        $this->timeout = 5000;
    }

    /**
     * @param int $timeout msec
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(JobExecutionContext $context)
    {
        $data = $context->getMergedJobDataMap();

        if (empty($data['topic'])) {
            $context->getTrigger()->setErrorMessage('There is no enqueue topic');

            $context->setUnscheduleFiringTrigger();

            return;
        }

        $this->rpcClient->call($data['topic'], $data, $this->timeout);
    }
}
