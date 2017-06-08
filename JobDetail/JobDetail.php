<?php
namespace Quartz\JobDetail;

use Makasim\Values\ValuesTrait;
use Quartz\Core\JobDetail as BaseJobDetail;
use Quartz\Core\Key;

class JobDetail implements BaseJobDetail
{
    use ValuesTrait;

    /**
     * @var Key
     */
    private $key;

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        if ($this->key) {
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
