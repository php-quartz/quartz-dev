<?php

namespace Quartz\Bundle;

use Quartz\Bridge\DI\QuartzJobCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class QuartzBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new QuartzJobCompilerPass());
    }
}
