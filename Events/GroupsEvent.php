<?php
namespace Quartz\Events;

class GroupsEvent extends Event
{
    /**
     * @var string[]|null
     */
    private $groups;

    /**
     * @param string[]|null $groups
     */
    public function __construct(array $groups = null)
    {
        $this->groups = $groups;
    }

    /**
     * @return string[]|null
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
