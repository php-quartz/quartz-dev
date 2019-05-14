<?php
namespace Quartz\Bridge\Scheduler;

use Enqueue\SimpleClient\SimpleClient;
use Quartz\Bridge\Enqueue\EnqueueRemoteTransport;
use Quartz\Bridge\Enqueue\EnqueueRemoteTransportProcessor;
use Quartz\Bridge\Enqueue\EnqueueResponseJob;
use Quartz\Bridge\Yadm\SimpleStoreResource;
use Quartz\Bridge\Yadm\YadmStore;
use Quartz\Core\Scheduler;
use Quartz\Core\SchedulerFactory as BaseSchedulerFactory;
use Quartz\Core\SimpleJobFactory;
use Quartz\Scheduler\JobRunShellFactory;
use Quartz\Scheduler\StdJobRunShell;
use Quartz\Scheduler\StdJobRunShellFactory;
use Quartz\Scheduler\StdScheduler;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SchedulerFactory implements BaseSchedulerFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * @var YadmStore
     */
    private $store;

    /**
     * @var SimpleClient
     */
    private $enqueue;

    /**
     * @var SimpleJobFactory
     */
    private $jobFactory;

    /**
     * @var JobRunShellFactory
     */
    private $jobRunShellFactory;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduler()
    {
        if (null == $this->scheduler) {
            $eventDispatcher = new EventDispatcher();

            $this->scheduler = new StdScheduler(
                $this->getStore(),
                $this->getJobRunShellFactory(),
                $this->getJobFactory(),
                $eventDispatcher
            );
        }

        return $this->scheduler;
    }

    public function getRemoteScheduler()
    {
        $transport = new EnqueueRemoteTransport($this->getEnqueue()->getProducerV2());

        return new RemoteScheduler($transport, new RpcProtocol());
    }

    public function getJobRunShellFactory()
    {
        if (null == $this->jobRunShellFactory) {
            $runJobShell = new EnqueueJobRunShell($this->getEnqueue()->getProducerV2());
            $this->jobRunShellFactory = new StdJobRunShellFactory($runJobShell);
        }

        return $this->jobRunShellFactory;
    }

    /**
     * @return SimpleJobFactory
     */
    public function getJobFactory()
    {
        if (null == $this->jobFactory) {
            $job = new EnqueueResponseJob($this->getEnqueue()->getProducerV2());

            $this->jobFactory = new SimpleJobFactory([
                EnqueueResponseJob::class => $job,
            ]);
        }

        return $this->jobFactory;
    }

    /**
     * @return JobRunShellProcessor
     */
    public function getJobRunShellProcessor()
    {
        $jobRunShell = new StdJobRunShell();
        $jobRunShell->initialize($this->getScheduler());

        return new JobRunShellProcessor($this->getStore(), $jobRunShell);
    }

    /**
     * @return EnqueueRemoteTransportProcessor
     */
    public function getRemoteSchedulerProcessor()
    {
        return new EnqueueRemoteTransportProcessor($this->getScheduler(), new RpcProtocol());
    }

    /**
     * @return YadmStore
     */
    public function getStore()
    {
        if (null == $this->store) {
            $config = isset($this->config['store']) ? $this->config['store'] : [];
            $this->store = new YadmStore(new SimpleStoreResource($config));
        }

        return $this->store;
    }

    /**
     * @return SimpleClient
     */
    public function getEnqueue()
    {
        if (null == $this->enqueue) {
            $config = isset($this->config['enqueue']) ? $this->config['enqueue'] : [];
            $this->enqueue = new SimpleClient($config);
        }

        return $this->enqueue;
    }
}
