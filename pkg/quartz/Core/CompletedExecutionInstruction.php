<?php
namespace Quartz\Core;

interface CompletedExecutionInstruction
{
    const NOOP = 'noop';
    const RE_EXECUTE_JOB = 're_execute_job';
    const SET_TRIGGER_COMPLETE = 'set_trigger_complete';
    const DELETE_TRIGGER = 'delete_trigger';
    const SET_ALL_JOB_TRIGGERS_COMPLETE = 'set_all_job_triggers_complete';
    const SET_TRIGGER_ERROR = 'set_trigger_error';
    const SET_ALL_JOB_TRIGGERS_ERROR = 'set_all_job_triggers_error';
}
