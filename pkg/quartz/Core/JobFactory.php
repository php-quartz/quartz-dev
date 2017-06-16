<?php
namespace Quartz\Core;

/**
 * <p>
 * A JobFactory is responsible for producing instances of <code>Job</code>
 * classes.
 * </p>
 *
 * <p>
 * This interface may be of use to those wishing to have their application
 * produce <code>Job</code> instances via some special mechanism, such as to
 * give the opportunity for dependency injection.
 * </p>
 */
interface JobFactory
{

    /**
     * Called by the scheduler at the time of the trigger firing, in order to
     * produce a <code>Job</code> instance on which to call execute.
     *
     * <p>
     * It should be extremely rare for this method to throw an exception -
     * basically only the case where there is no way at all to instantiate
     * and prepare the Job for execution.  When the exception is thrown, the
     * Scheduler will move all triggers associated with the Job into the
     * <code>Trigger.STATE_ERROR</code> state, which will require human
     * intervention (e.g. an application restart after fixing whatever
     * configuration problem led to the issue wih instantiating the Job.
     * </p>
     *
     * @param JobDetail $jobDetail
     *
     * @return Job
     */
    public function newJob(JobDetail $jobDetail);
}
