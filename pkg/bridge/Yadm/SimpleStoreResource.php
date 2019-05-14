<?php
namespace Quartz\Bridge\Yadm;

use Formapro\Yadm\ClientProvider;
use Formapro\Yadm\CollectionFactory;
use Formapro\Yadm\PessimisticLock;
use MongoDB\Client;
use MongoDB\Driver\Exception\RuntimeException;

class SimpleStoreResource implements StoreResource
{
    private $options;

    private $clientProvider;

    private $collectionFactory;

    private $managementLock;

    private $jobStorage;

    private $calendarStorage;

    private $triggerStorage;

    private $firedTriggerStorage;

    private $pausedTriggerStorage;

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

    public function getClient(): Client
    {
        return $this->getClientProvider()->getClient();
    }

    public function getClientProvider(): ClientProvider
    {
        if (false == $this->clientProvider) {
            $this->clientProvider = new ClientProvider($this->options['uri'], $this->options['uriOptions'], $this->options['driverOptions']);
        }

        return $this->clientProvider;
    }

    public function getCollectionFactory(): CollectionFactory
    {
        if (false == $this->collectionFactory) {
            $this->collectionFactory = new CollectionFactory($this->getClientProvider(), $this->options['uri']);
        }

        return $this->collectionFactory;
    }

    public function getManagementLock(): PessimisticLock
    {
        if (false == $this->managementLock) {
            $collection = $this->getCollectionFactory()->create($this->options['managementLockCol']);

            $this->managementLock = new PessimisticLock($collection, $this->options['sessionId']);
        }

        return $this->managementLock;
    }

    public function getCalendarStorage(): CalendarStorage
    {
        if (false == $this->calendarStorage) {
            $this->calendarStorage = new CalendarStorage(
                $this->options['calendarCol'],
                $this->getCollectionFactory(),
                new ModelHydrator()
            );
        }

        return $this->calendarStorage;
    }

    public function getTriggerStorage(): TriggerStorage
    {
        if (false == $this->triggerStorage) {
            $this->triggerStorage = new TriggerStorage(
                $this->options['triggerCol'],
                $this->getCollectionFactory(),
                new ModelHydrator()
            );
        }

        return $this->triggerStorage;
    }

    public function getFiredTriggerStorage(): FiredTriggerStorage
    {
        if (false == $this->firedTriggerStorage) {
            $this->firedTriggerStorage = new FiredTriggerStorage(
                $this->options['firedTriggerCol'],
                $this->getCollectionFactory(),
                new ModelHydrator()
            );
        }

        return $this->firedTriggerStorage;
    }

    public function getJobStorage(): JobStorage
    {
        if (false == $this->jobStorage) {
            $this->jobStorage = new JobStorage(
                $this->options['jobCol'],
                $this->getCollectionFactory(),
                new ModelHydrator()
            );
        }

        return $this->jobStorage;
    }

    public function getPausedTriggerStorage(): PausedTriggerStorage
    {
        if (false == $this->pausedTriggerStorage) {
            $this->pausedTriggerStorage = new PausedTriggerStorage(
                $this->options['pausedTriggerCol'],
                $this->getCollectionFactory(),
                new ModelHydrator()
            );
        }

        return $this->pausedTriggerStorage;
    }
}
