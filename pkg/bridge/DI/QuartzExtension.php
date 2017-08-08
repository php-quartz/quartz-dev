<?php
namespace Quartz\Bridge\DI;

use Quartz\Bridge\Enqueue\EnqueueRemoteTransportProcessor;
use Quartz\Bridge\Enqueue\EnqueueResponseJob;
use Quartz\Bridge\Scheduler\EnqueueJobRunShell;
use Quartz\Bridge\Scheduler\JobRunShellProcessor;
use Quartz\Bridge\Scheduler\RpcProtocol;
use Quartz\Core\SimpleJobFactory;
use Quartz\Scheduler\StdJobRunShell;
use Quartz\Scheduler\StdJobRunShellFactory;
use Quartz\Scheduler\StdScheduler;
use Quartz\Scheduler\Store\YadmStore;
use Quartz\Scheduler\Store\YadmStoreResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class QuartzExtension extends Extension
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @param string $alias
     */
    public function __construct($alias = 'quartz')
    {
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new QuartzConfiguration(), $configs);

        $container->register($this->format('store_resource'), YadmStoreResource::class)
            ->setArguments([$config['store']])
        ;

        $container->register($this->format('store'), YadmStore::class)
            ->setArguments([new Reference($this->format('store_resource'))])
            ->addMethodCall('setMisfireThreshold', [$config['misfireThreshold']])
        ;

        $container->register($this->format('enqueue_job_run_shell'), EnqueueJobRunShell::class)
            ->setArguments([new Reference('enqueue.producer')])
        ;

        $container->register($this->format('job_run_shell_factory'), StdJobRunShellFactory::class)
            ->setArguments([new Reference($this->format('enqueue_job_run_shell'))])
        ;

        $container->register($this->format('job_factory'), SimpleJobFactory::class)
            ->setArguments([[]])
        ;

        // TODO: add config option where can enable/disable this job
        $container->register($this->format('job.enqueue_response'), EnqueueResponseJob::class)
            ->setArguments([new Reference('enqueue.producer')])
            ->addTag($this->format('job'), ['alias' => 'enqueue_response'])
            ->addTag($this->format('job'), ['alias' => EnqueueResponseJob::class])
        ;

        $container->register($this->format('scheduler'), StdScheduler::class)
            ->setArguments([
                new Reference($this->format('store')),
                new Reference($this->format('job_run_shell_factory')),
                new Reference($this->format('job_factory')),
                new Reference($this->format('event_dispatcher'))
            ])
        ;

        $container->register($this->format('std_job_run_shell'), StdJobRunShell::class)
            ->addMethodCall('initialize', [new Reference($this->format('scheduler'))])
        ;

        $container->register($this->format('job_run_shell_processor'), JobRunShellProcessor::class)
            ->setArguments([
                new Reference($this->format('store')),
                new Reference($this->format('std_job_run_shell'))
            ])
            ->addTag('enqueue.client.processor')
        ;

        $container->register($this->format('rpc_protocol'), RpcProtocol::class)
            ->setPublic(false)
        ;

        $container->register($this->format('remote_transport_processor'), EnqueueRemoteTransportProcessor::class)
            ->setArguments([
                new Reference($this->format('scheduler')),
                new Reference($this->format('rpc_protocol'))
            ])
            ->addTag('enqueue.client.processor')
        ;
    }

    /**
     * @param string $service
     *
     * @return string
     */
    private function format($service)
    {
        return $this->alias.'.'.$service;
    }
}
