<?php
namespace Quartz\JobDetail;

use Formapro\Values\ValuesTrait;
use Quartz\Core\JobDetail as BaseJobDetail;
use Quartz\Core\Key;
use Quartz\Core\Model;

class JobDetail implements Model, BaseJobDetail
{
    use ValuesTrait;

    const INSTANCE = 'job-detail';

    /**
     * @var Key
     */
    private $key;

    public function __construct()
    {
        $this->setInstance(self::INSTANCE);
    }

    /**
     * @param string $instance
     */
    protected function setInstance($instance)
    {
        $this->setValue('instance', $instance);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        if (null == $this->key) {
            $this->key = new Key($this->getValue('name'), $this->getValue('group'));
        }

        return $this->key;
    }

    /**
     * @param Key $key
     */
    public function setKey(Key $key)
    {
        $this->key = $key;

        $this->setValue('name', $key->getName());
        $this->setValue('group', $key->getGroup());
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->getValue('description');
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->setValue('description', $description);
    }

    /**
     * {@inheritdoc}
     */
    public function isDurable()
    {
        return (bool) $this->getValue('durable');
    }

    /**
     * @param bool $durable
     */
    public function setDurable($durable)
    {
        $this->setValue('durable', (bool) $durable);
    }

    /**
     * {@inheritdoc}
     */
    public function getJobClass()
    {
        return $this->getValue('jobClass');
    }

    /**
     * @param string $class
     */
    public function setJobClass($class)
    {
        $this->setValue('jobClass', $class);
    }

    /**
     * {@inheritdoc}
     */
    public function getJobDataMap()
    {
        return $this->getValue('jobDataMap', []);
    }

    /**
     * @param array $jobDataMap
     */
    public function setJobDataMap(array $jobDataMap)
    {
        $this->setValue('jobDataMap', $jobDataMap);
    }

    /**
     * TODO: is not implemented :(
     *
     * {@inheritdoc}
     */
    public function requestsRecovery()
    {
        return $this->getValue('requestsRecovery');
    }
}
