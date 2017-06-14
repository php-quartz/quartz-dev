<?php
namespace Quartz\App;

use Enqueue\Client\RpcClient;
use Enqueue\Rpc\TimeoutException;
use Quartz\Core\Job;
use Quartz\Core\JobExecutionContext;

class EnqueueResponseJob implements Job
{
    /**
     * @var RpcClient
     */
    private $rpcClient;

    /**
     * @param RpcClient $rpcClient
     */
    public function __construct(RpcClient $rpcClient)
    {
        $this->rpcClient = $rpcClient;
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

        try {
            $this->rpcClient->call($data['topic'], $data, 5000);
        } catch (TimeoutException $e) {
            // TODO: handle error statuses
        }
    }
}
