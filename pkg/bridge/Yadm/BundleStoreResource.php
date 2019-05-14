<?php
namespace Quartz\Bridge\Yadm;

use Formapro\Yadm\ClientProvider;
use Formapro\Yadm\CollectionFactory;
use Formapro\Yadm\PessimisticLock;
use Formapro\Yadm\Registry;
use MongoDB\Client;

class BundleStoreResource implements StoreResource
{
    private $options;

    private $clientProvider;

    private $collectionFactory;

    private $managementLock;

    private $registry;

    public function __construct(
        ClientProvider $clientProvider,
        CollectionFactory $collectionFactory,
        Registry $registry,
        array $options = []
    ) {
        $this->options = array_replace([
            'sessionId' => 'quartz',
            'managementLockCol' => 'quartz_management_lock',
            'calendarStorage' => 'quartz_calendar',
            'triggerStorage' => 'quartz_trigger',
            'firedTriggerStorage' => 'quartz_fired_trigger',
            'jobStorage' => 'quartz_job',
            'pausedTriggerStorage' => 'quartz_paused_trigger',
        ], $options);

        $this->clientProvider = $clientProvider;
        $this->collectionFactory = $collectionFactory;
        $this->registry = $registry;
    }

    public function getClient(): Client
    {
        return $this->getClientProvider()->getClient();
    }

    public function getClientProvider(): ClientProvider
    {
        return $this->clientProvider;
    }

    public function getCollectionFactory(): CollectionFactory
    {
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
        return $this->registry->getStorage($this->options['calendarStorage']);
    }

    public function getTriggerStorage(): TriggerStorage
    {
        return $this->registry->getStorage($this->options['triggerStorage']);
    }

    public function getFiredTriggerStorage(): FiredTriggerStorage
    {
        return $this->registry->getStorage($this->options['firedTriggerStorage']);
    }

    public function getJobStorage(): JobStorage
    {
        return $this->registry->getStorage($this->options['jobStorage']);
    }

    public function getPausedTriggerStorage(): PausedTriggerStorage
    {
        return $this->registry->getStorage($this->options['pausedTriggerStorage']);
    }
}
