<?php
namespace Quartz\Core;

use Makasim\Values\ValuesTrait;
use Ramsey\Uuid\Uuid;

class Key implements Model
{
    use ValuesTrait;

    const INSTANCE = 'key';

    const DEFAULT_GROUP = 'DEFAULT';
    const GROUP_NS = '5f8ad9bf-247b-43bc-8ef5-886e701bd744';

    /**
     * @param string $name
     * @param string $group
     */
    public function __construct($name, $group = null)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        $this->setInstance(self::INSTANCE);

        $this->setName($name);
        $this->setGroup(empty($group) ? self::DEFAULT_GROUP : $group);
    }

    /**
     * @param string $instance
     */
    protected function setInstance($instance)
    {
        $this->setValue('instance', $instance);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getValue('name');
    }

    /**
     * @param string $name
     */
    private function setName($name)
    {
        $this->setValue('name', $name);
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->getValue('group');
    }

    /**
     * @param string $group
     */
    private function setGroup($group)
    {
        $this->setValue('group', $group);
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
