<?php declare(strict_types=1);

namespace Quartz\Bridge\Yadm;

use Formapro\Yadm\Index;
use Formapro\Yadm\Storage;
use Formapro\Yadm\StorageMetaInterface;
use Quartz\Core\Trigger;

/**
 * @method Trigger|null           create()
 * @method Trigger|null           findOne(array $filter = [], array $options = [])
 * @method Trigger[]|\Traversable find(array $filter = [], array $options = [])
 */
class TriggerStorage extends Storage implements StorageMetaInterface
{
    public function getIndexes(): array
    {
        return [
            new Index(['name' => 1, 'group' => 1], ['unique' => true]),
            new Index(['group' => 1]),
            new Index(['jobName' => 1, 'jobGroup' => 1]),
            new Index(['jobGroup' => 1]),
            new Index(['calendarName' => 1]),
            new Index(['state' => 1]),
            new Index(['nextFireTime.unix' => 1]),
        ];
    }

    public function getCreateCollectionOptions(): array
    {
        return [];
    }
}