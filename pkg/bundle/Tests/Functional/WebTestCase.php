<?php

namespace Quartz\Bundle\Tests\Functional;

use Quartz\Bundle\Tests\Functional\App\AppKernel;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

abstract class WebTestCase extends BaseWebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        parent::setUp();

        static::$class = null;

        $this->client = static::createClient();
        static::$container = static::$kernel->getContainer();
    }

    /**
     * @return string
     */
    public static function getKernelClass()
    {
        include_once __DIR__.'/App/AppKernel.php';

        return AppKernel::class;
    }
}
