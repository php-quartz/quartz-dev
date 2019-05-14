<?php
namespace Quartz\Bridge\Yadm;

use Formapro\Yadm\ClientProvider;
use Formapro\Yadm\CollectionFactory;
use Formapro\Yadm\PessimisticLock;
use MongoDB\Client;
use MongoDB\Collection;

interface StoreResource
{
    public function getClient(): Client;

    public function getClientProvider(): ClientProvider;

    public function getCollectionFactory(): CollectionFactory;

    public function getManagementLock(): PessimisticLock;

    public function getCalendarStorage(): CalendarStorage;

    public function getTriggerStorage(): TriggerStorage;

    public function getFiredTriggerStorage(): FiredTriggerStorage;

    public function getJobStorage(): JobStorage;

    public function getPausedTriggerStorage(): PausedTriggerStorage;
}
