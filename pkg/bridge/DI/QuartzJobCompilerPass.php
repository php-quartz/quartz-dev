<?php

namespace Quartz\Bridge\DI;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class QuartzJobCompilerPass implements CompilerPassInterface
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
    public function process(ContainerBuilder $container)
    {
        $jobFactory = $container->getDefinition($this->format('job_factory'));
        $tags = $container->findTaggedServiceIds($this->format('job'));

        $jobs = [];
        foreach ($tags as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == empty($tagAttribute['alias'])) {
                    $jobName = $tagAttribute['alias'];
                } else {
                    $jobName = $container->getDefinition($serviceId)->getClass();
                }

                $jobs[$jobName] = new Reference($serviceId);
            }
        }

        $jobFactory->replaceArgument(0, $jobs);
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
