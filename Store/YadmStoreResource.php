<?php
namespace Quartz\Store;

use Makasim\Yadm\Hydrator;
use Makasim\Yadm\PessimisticLock;
use Makasim\Yadm\Storage;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Exception\RuntimeException;
use Quartz\Calendar\CalendarClassFactoryTrait;
use Quartz\JobDetail\JobDetail;
use Quartz\Triggers\TriggerClassFactoryTrait;

class YadmStoreResource
{
    use CalendarClassFactoryTrait;
    use TriggerClassFactoryTrait;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var PessimisticLock
     */
    private $managementLock;

    /**
     * @var Storage
     */
    private $calendarStorage;

    /**
     * @var Storage
     */
    private $triggerStorage;

    /**
     * @var Storage
     */
    private $firedTriggerStorage;

    /**
     * @var Storage
     */
    private $jobStorage;

    /**
     * @var Collection
     */
    private $pausedTriggerCol;

    public function __construct(array $options = [])
    {
        $this->options = array_replace([
            'uri' => 'mongodb://localhost:27017',
            'uriOptions' => [],
            'driverOptions' => [],
            'sessionId' => 'quartz',
            'dbName' => 'quartz',
            'managementLockCol' => 'managementLock',
            'calendarCol' => 'calendar',
            'triggerCol' => 'trigger',
            'firedTriggerCol' => 'firedTrigger',
            'jobCol' => 'job',
            'pausedTriggerCol' => 'pausedTrigger',

        ], $options);
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if (false == $this->client) {
            $this->client = new Client($this->options['uri'], $this->options['uriOptions'], $this->options['driverOptions']);
        }

        return $this->client;
    }

    /**
     * @param string $name
     *
     * @return \MongoDB\Collection
     */
    public function getCollection($name)
    {
        return $this->getClient()->selectCollection($this->options['dbName'], $name);
    }

    /**
     * @return PessimisticLock
     */
    public function getManagementLock()
    {
        if (false == $this->managementLock) {
            $this->managementLock = new PessimisticLock($this->getCollection($this->options['managementLockCol']), $this->options['sessionId']);
        }

        return $this->managementLock;
    }

    /**
     * @return Storage
     */
    public function getCalendarStorage()
    {
        if (false == $this->calendarStorage) {
            $collection = $this->getCollection($this->options['calendarCol']);
            $hydrator = new Hydrator(function ($values){ return $this->getCalendarClass($values); });

            $this->calendarStorage = new Storage($collection, $hydrator);
        }

        return $this->calendarStorage;
    }

    /**
     * @return Storage
     */
    public function getTriggerStorage()
    {
        if (false == $this->triggerStorage) {
            $collection = $this->getCollection($this->options['triggerCol']);
            $hydrator = new Hydrator(function ($values){ return $this->getTriggerClass($values); });

            $this->triggerStorage = new Storage($collection, $hydrator);
        }

        return $this->triggerStorage;
    }

    /**
     * @return Storage
     */
    public function getFiredTriggerStorage()
    {
        if (false == $this->firedTriggerStorage) {
            $collection = $this->getCollection($this->options['firedTriggerCol']);
            $hydrator = new Hydrator(function ($values){ return $this->getTriggerClass($values); });

            $this->firedTriggerStorage = new Storage($collection, $hydrator);
        }

        return $this->firedTriggerStorage;
    }

    /**
     * @return Storage
     */
    public function getJobStorage()
    {
        if (false == $this->jobStorage) {
            $collection = $this->getCollection($this->options['jobCol']);
            $hydrator = new Hydrator(JobDetail::class);

            $this->jobStorage = new Storage($collection, $hydrator);
        }

        return $this->jobStorage;
    }

    /**
     * @return Collection
     */
    public function getPausedTriggerCol()
    {
        if (false == $this->pausedTriggerCol) {
            $this->pausedTriggerCol = $this->getCollection($this->options['pausedTriggerCol']);
        }

        return $this->pausedTriggerCol;
    }

    public function createIndexes()
    {
        try {
            $this->getTriggerStorage()->getCollection()->dropIndexes();
            $this->getJobStorage()->getCollection()->dropIndexes();
            $this->getCalendarStorage()->getCollection()->dropIndexes();
            $this->getPausedTriggerCol()->dropIndexes();
            $this->getFiredTriggerStorage()->getCollection()->dropIndexes();
        } catch (RuntimeException $e) {
        }

        $this->getManagementLock()->createIndexes();

        $this->getTriggerStorage()->getCollection()->createIndexes([
            [
                'key' => ['key' => 1, 'group' => 1], 'unique' => true,
            ],
            [
                'key' => ['group' => 1],
            ],
            [
                'key' => ['jobKey' => 1, 'jobGroup' => 1],
            ],
            [
                'key' => ['jobGroup' => 1],
            ],
            [
                'key' => ['calendarName' => 1],
            ],
            [
                'key' => ['state' => 1],
            ],
            [
                'key' => ['nextFireTime.unix' => 1],
            ],
        ]);

        $this->getJobStorage()->getCollection()->createIndexes([
            [
                'key' => ['key' => 1, 'group' => 1], 'unique' => true,
            ],
            [
                'key' => ['group' => 1],
            ],
        ]);

        $this->getCalendarStorage()->getCollection()->createIndexes([
            [
                'key' => ['name' => 1], 'unique' => true,
            ],
        ]);

        $this->getPausedTriggerCol()->createIndexes([
            [
                'key' => ['groupName' => 1],
            ],
        ]);

        $this->getFiredTriggerStorage()->getCollection()->createIndexes([
            [
                'key' => ['fireInstanceId' => 1],
            ],
        ]);
    }
}
