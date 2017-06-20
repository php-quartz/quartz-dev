<?php
namespace Quartz\Core;

/**
 * An exception that is thrown to indicate that an attempt to store a new
 * object (i.e. <code>{@link JobDetail}</code>,<code>{@link Trigger}</code>
 * or <code>{@link Calendar}</code>) in a <code>{@link Scheduler}</code>
 * failed, because one with the same name & group already exists.
 */
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
