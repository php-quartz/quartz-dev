<?php
namespace Quartz\Store;

use Quartz\Core\JobDetail;
use Quartz\Core\JobPersistenceException;
use Quartz\Core\Trigger;

class ObjectAlreadyExistsException extends JobPersistenceException
{
    /**
     * @param object $object
     *
     * @return ObjectAlreadyExistsException
     */
    public static function create($object)
    {
        if ($object instanceof JobDetail) {
            $message = sprintf(
                'Unable to store Job : "%s", because one already exists with this identification.', $object->getKey()
            );
        } elseif ($object instanceof Trigger) {
            $message = sprintf(
                'Unable to store Trigger with name: "%s" and group: "%s", because one already exists with this identification.'
                , $object->getKey()->getName(), $object->getKey()->getGroup()
            );
        } else {
            $message = (string) $object;
        }

        return new static($message);
    }
}
