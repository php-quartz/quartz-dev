<?php
namespace Quartz\Events;

use Quartz\Core\Key;

class KeyEvent extends Event
{
    /**
     * @var Key
     */
    private $key;

    /**
     * @param Key $key
     */
    public function __construct(Key $key)
    {
        $this->key = $key;
    }

    /**
     * @return Key
     */
    public function getKey(): Key
    {
        return $this->key;
    }
}
