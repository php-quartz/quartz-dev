<?php
namespace Quartz\Core;

class StdJobRunShellFactory implements JobRunShellFactory
{
    /**
     * {@inheritdoc}
     */
    public function createJobRunShell(Trigger $trigger)
    {
        return new SyncJobRunShell();
    }
}
