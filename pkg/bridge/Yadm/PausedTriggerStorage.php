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
class PausedTriggerStorage extends Storage implements StorageMetaInterface
{
    public function getIndexes(): array
    {
        return [
            new Index(['groupName' => 1]),
        ];
    }

    public function getCreateCollectionOptions(): array
    {
        return [];
    }
}