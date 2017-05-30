<?php
namespace Quartz\JobDetail;

use Makasim\Values\ValuesTrait;
use Quartz\Core\JobDetail as BaseJobDetail;
use Quartz\Core\Key;

class JobDetail implements BaseJobDetail
{
    use ValuesTrait {
        getValue as public;
        setValue as public;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return new Key($this->getValue('name'), $this->getValue('group'));
    }

    /**
     * @param Key $key
     */
    public function setKey(Key $key)
    {
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
    public function getJobDataMap()
    {
        return $this->getValue('jobDataMap');
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
     * @param array $jobDataMap
     */
    public function setJobDataMap(array $jobDataMap)
    {
        $this->setValue('jobDataMap', $jobDataMap);
    }

    /**
     * {@inheritdoc}
     */
    public function requestsRecovery()
    {
        return $this->getValue('requestsRecovery');
    }
}
