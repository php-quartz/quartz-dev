<?php
namespace Quartz\Bridge\DI;

use Enqueue\Client\ProducerInterface;
use Quartz\Bridge\Enqueue\EnqueueRemoteTransport;
use Quartz\Bridge\Scheduler\RemoteScheduler;
use Quartz\Bridge\Scheduler\RpcProtocol;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class RemoteSchedulerExtension extends Extension
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
        $container->register($this->format('remote.transport'), EnqueueRemoteTransport::class)
            ->setArguments([new Reference(ProducerInterface::class)])
        ;

        $container->register($this->format('remote.rpc_protocol'), RpcProtocol::class)
            ->setPublic(false)
        ;

        $container->register($this->format('remote.scheduler'), RemoteScheduler::class)
            ->setArguments([
                new Reference($this->format('remote.transport')),
                new Reference($this->format('remote.rpc_protocol')),
            ])
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
