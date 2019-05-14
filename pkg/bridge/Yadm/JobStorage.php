<?php declare(strict_types=1);

namespace Quartz\Bridge\Yadm;

use Formapro\Yadm\Index;
use Formapro\Yadm\Storage;
use Formapro\Yadm\StorageMetaInterface;
use Quartz\Core\Job;

/**
 * @method Job|null           create()
 * @method Job|null           findOne(array $filter = [], array $options = [])
 * @method Job[]|\Traversable find(array $filter = [], array $options = [])
 */
class JobStorage extends Storage implements StorageMetaInterface
{
    public function getIndexes(): array
    {
        return [
            new Index(['name' => 1, 'group' => 1], ['unique' => true]),
            new Index(['group' => 1]),
        ];
    }

    public function getCreateCollectionOptions(): array
    {
        return [];
    }
}
