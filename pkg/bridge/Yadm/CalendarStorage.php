<?php declare(strict_types=1);

namespace Quartz\Bridge\Yadm;

use Formapro\Yadm\Index;
use Formapro\Yadm\Storage;
use Formapro\Yadm\StorageMetaInterface;
use Quartz\Core\Calendar;

/**
 * @method Calendar|null           create()
 * @method Calendar|null           findOne(array $filter = [], array $options = [])
 * @method Calendar[]|\Traversable find(array $filter = [], array $options = [])
 */
class CalendarStorage extends Storage implements StorageMetaInterface
{
    public function getIndexes(): array
    {
        return [
            new Index(['name' => 1], ['unique' => true]),
        ];
    }

    public function getCreateCollectionOptions(): array
    {
        return [];
    }
}