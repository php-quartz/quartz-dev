<?php

namespace Quartz\Bundle\DependencyInjection;

use Quartz\Bridge\DI\QuartzExtension as QuartzSchedulerExtension;
use Quartz\Bridge\DI\RemoteSchedulerExtension;
use Quartz\Bundle\Command\ManagementCommand;
use Quartz\Bundle\Command\SchedulerCommand;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class QuartzExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        if (is_array($config['scheduler'])) {
            $schedulerExt = new QuartzSchedulerExtension($this->getAlias());
            $schedulerExt->load([$config['scheduler']], $container);

            $container->setAlias($this->format('event_dispatcher'), 'event_dispatcher');

            $container->register($this->format('cli.scheduler'), SchedulerCommand::class)
                ->setArguments([new Reference($this->format('scheduler'))])
                ->addTag('console.command')
            ;

            $container->register($this->format('cli.management'), ManagementCommand::class)
                ->setArguments([new Reference($this->format('scheduler'))])
                ->addTag('console.command')
            ;
        }

        if (is_array($config['remote_scheduler'])) {
            $remoteExt = new RemoteSchedulerExtension($this->getAlias());
            $remoteExt->load([$config['remote_scheduler']], $container);
        }
    }

    /**
     * @param string $service
     *
     * @return string
     */
    private function format($service)
    {
        return $this->getAlias().'.'.$service;
    }
}
