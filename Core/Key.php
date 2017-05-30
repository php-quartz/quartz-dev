<?php
namespace Quartz\Core;

use Ramsey\Uuid\Uuid;

class Key
{
    const DEFAULT_GROUP = 'DEFAULT';
    const GROUP_NS = '5f8ad9bf-247b-43bc-8ef5-886e701bd744';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $group;

    /**
     *
     * @param string $name
     * @param string $group
     */
    public function __construct($name, $group = null)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        $this->name = $name;
        $this->group = empty($group) ? self::DEFAULT_GROUP : $group;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string|null $group
     *
     * @return string
     */
    public static function createUniqueName($group = null)
    {
        $group = empty($group) ? self::DEFAULT_GROUP : $group;
        $group = Uuid::uuid3(self::GROUP_NS, $group)->toString();
        $name = Uuid::uuid4()->toString();

        return $group.'-'.$name;
    }

    /**
     * @param Key $key
     *
     * @return bool
     */
    public function equals(Key $key)
    {
        return ((string) $this) == ((string) $key);
    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->getGroup().'.'.$this->getName();
    }
}
