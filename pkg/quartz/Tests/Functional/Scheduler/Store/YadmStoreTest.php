<?php
namespace Quartz\Tests\Functional\Scheduler\Store;

use function Makasim\Values\register_cast_hooks;
use Quartz\Calendar\HolidayCalendar;
use Quartz\Core\CompletedExecutionInstruction;
use Quartz\Core\JobPersistenceException;
use Quartz\Core\Key;
use Quartz\Core\SchedulerException;
use Quartz\Core\Trigger;
use Quartz\JobDetail\JobDetail;
use Quartz\Core\ObjectAlreadyExistsException;
use Quartz\Scheduler\StdScheduler;
use Quartz\Scheduler\Store\YadmStore;
use Quartz\Scheduler\Store\YadmStoreResource;
use Quartz\Triggers\SimpleTrigger;

class YadmStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var YadmStore
     */
    private $store;

    /**
     * @var YadmStoreResource
     */
    private $res;

    public static function setUpBeforeClass()
    {
        register_cast_hooks();
    }

    protected function setUp()
    {
        parent::setUp();

        $config = [
            'uri' => sprintf('mongodb://%s:%s', getenv('MONGODB_HOST'), getenv('MONGODB_PORT')),
            'dbName' => getenv('MONGODB_DB')
        ];

        $this->res = new YadmStoreResource($config);
        $this->store = new YadmStore($this->res);
        $this->store->initialize($this->createMock(StdScheduler::class));
        $this->store->clearAllSchedulingData();
    }

    public function testCouldInsertNewPausedGroupAndGetThem()
    {
        // guard
        $this->assertEmpty($this->store->getPausedTriggerGroups());

        // test
        $this->store->insertPausedTriggerGroup('group1');
        $this->store->insertPausedTriggerGroup('group2');

        $this->assertSame(['group1', 'group2'], $this->store->getPausedTriggerGroups());
    }

    public function testCouldDeletePausedGroup()
    {
        // guard
        $this->store->insertPausedTriggerGroup('group1');
        $this->store->insertPausedTriggerGroup('group2');
        $this->assertSame(['group1', 'group2'], $this->store->getPausedTriggerGroups());

        // test
        $this->store->deletePausedTriggerGroup('group2');

        $this->assertSame(['group1'], $this->store->getPausedTriggerGroups());
    }

    public function testShouldReturnGroupIsPausedOrNot()
    {
        // guard
        $this->store->insertPausedTriggerGroup('group1');
        $this->assertSame(['group1'], $this->store->getPausedTriggerGroups());

        // test
        $this->assertTrue($this->store->isTriggerGroupPaused('group1'));
        $this->assertFalse($this->store->isTriggerGroupPaused('group2'));
    }

    public function testCouldStoreAndRetrieveCalendar()
    {
        // guard
        $this->assertNull($this->store->retrieveCalendar('theCal'));

        // test
        $calendar = new HolidayCalendar();
        $calendar->setDescription('theCalDescription');
        $this->store->storeCalendar('theCal', $calendar);

        $storedCalendar = $this->store->retrieveCalendar('theCal');

        $this->assertInstanceOf(HolidayCalendar::class, $storedCalendar);
        $this->assertSame('theCalDescription', $storedCalendar->getDescription());
    }

    public function testCouldRemoveCalendar()
    {
        $calendar = new HolidayCalendar();
        $this->store->storeCalendar('theCal', $calendar);

        // guard
        $this->assertNotNull($this->store->retrieveCalendar('theCal'));

        // test
        $this->assertTrue($this->store->removeCalendar('theCal'));
        $this->assertNull($this->store->retrieveCalendar('theCal'));
    }

    public function testThrowExceptionIfCalendarHasAssoiatedTriggers()
    {
        $calendar = new HolidayCalendar();

        $trigger = new SimpleTrigger();
        $trigger->setKey(new Key('name', 'group'));
        $trigger->setCalendarName('theCal');

        $this->store->storeCalendar('theCal', $calendar);
        $this->store->storeTrigger($trigger);

        $this->expectException(JobPersistenceException::class);
        $this->expectExceptionMessage('Calendar cannot be removed if it referenced by a trigger!. calendar: "theCal"');

        $this->store->removeCalendar('theCal');
    }

    public function testShouldThrowObjectAlreadyExistsWhenCalendarAlreadyExistsAndReplaceArgIsFalse()
    {
        $calendar = new HolidayCalendar();

        $this->expectException(ObjectAlreadyExistsException::class);
        $this->expectExceptionMessage('Calendar with name already exists: "theCal"');

        $this->store->storeCalendar('theCal', $calendar);
        $this->store->storeCalendar('theCal', $calendar);
    }

    public function testShouldReplaceExistingCalendarWithNewOne()
    {
        $calendar = new HolidayCalendar();
        $calendar->setDescription('desc1');

        $this->store->storeCalendar('theCal', $calendar);
        // guard
        $calendar = $this->store->retrieveCalendar('theCal');
        $this->assertSame('desc1', $calendar->getDescription());

        // test
        $calendar = new HolidayCalendar();
        $calendar->setDescription('desc2');

        $this->store->storeCalendar('theCal', $calendar, true);

        $calendar = $this->store->retrieveCalendar('theCal');
        $this->assertSame('desc2', $calendar->getDescription());
    }

    public function testCouldStoreAndRetrieveTrigger()
    {
        $key = new Key('name', 'group');

        // guard
        $this->assertNull($this->store->retrieveTrigger($key));

        // test
        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setDescription('theDesc');

        $this->store->storeTrigger($trigger);

        $this->assertInstanceOf(SimpleTrigger::class, $trigger = $this->store->retrieveTrigger($key));
        $this->assertSame('theDesc', $trigger->getDescription());
        $this->assertSame(Trigger::STATE_WAITING, $trigger->getState());
    }

    public function testShouldThrowObjectAlreadyExistsWhenTriggerExistsAndReplaceExistingIsFalse()
    {
        $trigger = new SimpleTrigger();
        $trigger->setKey(new Key('name', 'group'));
        $trigger->setDescription('theDesc');

        $this->store->storeTrigger($trigger);

        $this->expectException(ObjectAlreadyExistsException::class);
        $this->expectExceptionMessage('Unable to store Trigger with name: "name" and group: "group", because one already exists with this identification.');

        $this->store->storeTrigger($trigger);
    }

    public function testShouldSetTriggerStatePausedIfGroupIsPaused()
    {
        $key = new Key('name', 'group');

        // guard
        $this->assertNull($this->store->retrieveTrigger($key));

        // test
        $trigger = new SimpleTrigger();
        $trigger->setKey($key);

        $this->store->insertPausedTriggerGroup('group');
        $this->store->storeTrigger($trigger);

        $this->assertInstanceOf(SimpleTrigger::class, $trigger = $this->store->retrieveTrigger($key));
        $this->assertSame(Trigger::STATE_PAUSED, $trigger->getState());
    }

    public function testShouldSetTriggerStatePausedIfAllGroupsArePaused()
    {
        $key = new Key('name', 'group');

        // guard
        $this->assertNull($this->store->retrieveTrigger($key));

        // test
        $trigger = new SimpleTrigger();
        $trigger->setKey($key);

        $this->store->insertPausedTriggerGroup(YadmStore::ALL_GROUPS_PAUSED);
        $this->store->storeTrigger($trigger);

        $this->assertInstanceOf(SimpleTrigger::class, $trigger = $this->store->retrieveTrigger($key));
        $this->assertSame(Trigger::STATE_PAUSED, $trigger->getState());
    }

    public function testShouldReplaceTriggerWithNewTrigger()
    {
        $key = new Key('name', 'group');

        // guard
        $this->assertNull($this->store->retrieveTrigger($key));

        // test
        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setDescription('theDesc');

        $this->store->storeTrigger($trigger);
        $this->assertInstanceOf(SimpleTrigger::class, $trigger = $this->store->retrieveTrigger($key));
        $this->assertSame('theDesc', $trigger->getDescription());

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setDescription('theNewDesc');

        $this->store->storeTrigger($trigger, true);
        $this->assertInstanceOf(SimpleTrigger::class, $trigger = $this->store->retrieveTrigger($key));
        $this->assertSame('theNewDesc', $trigger->getDescription());
    }

    public function testCouldRemoveTrigger()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setJobKey(new Key('name', 'group'));

        $this->store->storeTrigger($trigger);

        // guard
        $this->assertNotNull($this->store->retrieveTrigger($key));

        // test
        $this->assertTrue($this->store->removeTrigger($key));
        $this->assertNull($this->store->retrieveTrigger($key));
    }

    public function testShouldRemoveTriggerAndJobIfNotDurable()
    {
        $key = new Key('name', 'group');
        $jobKey = new Key('job-name', 'job-group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setJobKey($jobKey);

        $job = new JobDetail();
        $job->setKey($jobKey);
        $job->setDurable(false);

        $this->store->storeTrigger($trigger);
        $this->store->storeJob($job);

        // guard
        $this->assertNotNull($this->store->retrieveTrigger($key));
        $this->assertNotNull($this->store->retrieveJob($jobKey));

        // test
        $this->assertTrue($this->store->removeTrigger($key));

        $this->assertNull($this->store->retrieveTrigger($key));
        $this->assertNull($this->store->retrieveJob($jobKey));
    }

    public function testShouldRemoveTriggerButNotJobIfJobIsDurable()
    {
        $key = new Key('name', 'group');
        $jobKey = new Key('job-name', 'job-group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setJobKey($jobKey);

        $job = new JobDetail();
        $job->setKey($jobKey);
        $job->setDurable(true);

        $this->store->storeTrigger($trigger);
        $this->store->storeJob($job);

        // guard
        $this->assertNotNull($this->store->retrieveTrigger($key));
        $this->assertNotNull($this->store->retrieveJob($jobKey));

        // test
        $this->assertTrue($this->store->removeTrigger($key));

        $this->assertNull($this->store->retrieveTrigger($key));
        $this->assertNotNull($this->store->retrieveJob($jobKey));
    }

    public function testShouldRemoveTriggerButNotJobIfJobReferencesToExistentTrigger()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');
        $jobKey = new Key('job-name', 'job-group');

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setJobKey($jobKey);

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setJobKey($jobKey);

        $job = new JobDetail();
        $job->setKey($jobKey);
        $job->setDurable(false);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);

        $this->store->storeJob($job);

        // guard
        $this->assertNotNull($this->store->retrieveTrigger($key1));
        $this->assertNotNull($this->store->retrieveTrigger($key2));
        $this->assertNotNull($this->store->retrieveJob($jobKey));

        // test
        $this->assertTrue($this->store->removeTrigger($key1));

        $this->assertNull($this->store->retrieveTrigger($key1));
        $this->assertNotNull($this->store->retrieveTrigger($key2));
        $this->assertNotNull($this->store->retrieveJob($jobKey));
    }

    public function testShouldRemoveManyTriggers()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setJobKey(new Key('job-name', 'job-group'));

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setJobKey(new Key('job-name', 'job-group'));

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);

        // guard
        $this->assertNotNull($this->store->retrieveTrigger($key1));
        $this->assertNotNull($this->store->retrieveTrigger($key2));

        // test
        $this->assertTrue($this->store->removeTriggers([$key2, $key1]));

        $this->assertNull($this->store->retrieveTrigger($key1));
        $this->assertNull($this->store->retrieveTrigger($key2));
    }

    public function testShouldThrowInvalidArgumentOnRemoveManyTriggersIfArrayContainsNotKeyInstance()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->store->removeTriggers([new \stdClass()]);
    }

    public function testShouldReturnTrueIfTriggerExists()
    {
        $key = new Key('name', 'group');

        // guard
        $this->assertNull($this->store->retrieveTrigger($key));
        $this->assertFalse($this->store->checkTriggerExists($key));

        // test
        $trigger = new SimpleTrigger();
        $trigger->setKey($key);

        $this->store->storeTrigger($trigger);

        $this->assertTrue($this->store->checkTriggerExists($key));
    }

    public function testCouldStoreAndRetrieveJob()
    {
        $key = new Key('name', 'group');

        // guard
        $this->assertNull($this->store->retrieveJob($key));

        // test
        $job = new JobDetail();
        $job->setKey($key);
        $job->setDescription('theDesc');

        $this->store->storeJob($job);

        $this->assertInstanceOf(JobDetail::class, $job = $this->store->retrieveJob($key));
        $this->assertSame('theDesc', $job->getDescription());
    }

    public function testShouldThrowObjectAlreadyExistsWhenJobExistsAndReplaceExistingIsFalse()
    {
        $job = new JobDetail();
        $job->setKey(new Key('name', 'group'));
        $job->setDescription('theDesc');

        $this->store->storeJob($job);

        $this->expectException(ObjectAlreadyExistsException::class);
        $this->expectExceptionMessage('Unable to store Job : "group.name", because one already exists with this identification.');

        $this->store->storeJob($job);
    }

    public function testShouldReplaceJobWithNewJob()
    {
        $key = new Key('name', 'group');

        // guard
        $this->assertNull($this->store->retrieveJob($key));

        // test
        $job = new JobDetail();
        $job->setKey($key);
        $job->setDescription('theDesc');

        $this->store->storeJob($job);
        $this->assertInstanceOf(JobDetail::class, $job = $this->store->retrieveJob($key));
        $this->assertSame('theDesc', $job->getDescription());

        $job = new JobDetail();
        $job->setKey($key);
        $job->setDescription('theNewDesc');

        $this->store->storeJob($job, true);
        $this->assertInstanceOf(JobDetail::class, $job = $this->store->retrieveJob($key));
        $this->assertSame('theNewDesc', $job->getDescription());
    }

    public function testCouldRemoveJob()
    {
        $key = new Key('name', 'group');

        $job = new JobDetail();
        $job->setKey($key);

        $this->store->storeJob($job);

        // guard
        $this->assertNotNull($this->store->retrieveJob($key));

        // test
        $this->assertTrue($this->store->removeJob($key));
        $this->assertNull($this->store->retrieveJob($key));
    }

    public function testShouldRemoveJobAndAssociatedTriggers()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');
        $jobKey = new Key('job-name', 'job-group');

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setJobKey($jobKey);

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setJobKey($jobKey);

        $job = new JobDetail();
        $job->setKey($jobKey);
        $job->setDurable(false);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);
        $this->store->storeJob($job);

        // guard
        $this->assertNotNull($this->store->retrieveJob($jobKey));
        $this->assertNotNull($this->store->retrieveTrigger($key1));
        $this->assertNotNull($this->store->retrieveTrigger($key2));

        // test
        $this->assertTrue($this->store->removeJob($jobKey));

        $this->assertNull($this->store->retrieveJob($jobKey));
        $this->assertNull($this->store->retrieveTrigger($key1));
        $this->assertNull($this->store->retrieveTrigger($key2));
    }

    public function testShouldRemoveManyJobs()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');

        $job1 = new JobDetail();
        $job1->setKey($key1);

        $job2 = new JobDetail();
        $job2->setKey($key2);

        $this->store->storeJob($job1);
        $this->store->storeJob($job2);

        // guard
        $this->assertNotNull($this->store->retrieveJob($key1));
        $this->assertNotNull($this->store->retrieveJob($key2));

        // test
        $this->assertTrue($this->store->removeJobs([$key2, $key1]));

        $this->assertNull($this->store->retrieveJob($key1));
        $this->assertNull($this->store->retrieveJob($key2));
    }

    public function testShouldThrowInvalidArgumentOnRemoveManyJobsIfArrayContainsNotKeyInstance()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->store->removeJobs([new \stdClass()]);
    }

    public function testShouldReturnTrueIfJobExists()
    {
        $key = new Key('name', 'group');

        // guard
        $this->assertNull($this->store->retrieveJob($key));
        $this->assertFalse($this->store->checkJobExists($key));

        // test
        $job = new JobDetail();
        $job->setKey($key);

        $this->store->storeJob($job);

        $this->assertTrue($this->store->checkJobExists($key));
    }

    public function testCouldStoreJobAndTrigger()
    {
        $key = new Key('name', 'group');
        $jobKey = new Key('job-name', 'job-group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);

        $job = new JobDetail();
        $job->setKey($jobKey);

        // guard
        $this->assertNull($this->store->retrieveTrigger($key));
        $this->assertNull($this->store->retrieveJob($jobKey));

        // test
        $this->store->storeJobAndTrigger($job, $trigger);

        $this->assertNotNull($this->store->retrieveTrigger($key));
        $this->assertNotNull($this->store->retrieveJob($jobKey));
    }

    public function testCouldPauseTrigger()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);

        $this->store->storeTrigger($trigger);

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key)->getState());

        $this->store->pauseTrigger($key);

        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key)->getState());
    }

    public function testOnPauseJobShouldPauseAllAssociatedTriggers()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');
        $jobKey = new Key('job-name', 'job-group');

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setJobKey(clone $jobKey);

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setJobKey(clone $jobKey);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key2)->getState());

        $this->store->pauseJob($jobKey);

        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key2)->getState());
    }

    public function testShouldResumeTrigger()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setNextFireTime(new \DateTime('+1 minute'));

        $this->store->storeTrigger($trigger);
        $this->store->pauseTrigger($key);

        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key)->getState());

        $this->store->resumeTrigger($key);

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key)->getState());
    }

    public function testOnResumeTriggerShouldUpdateMisfiredTrigger()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setNextFireTime(new \DateTime('-1 hours'));
        $trigger->setMisfireInstruction(SimpleTrigger::MISFIRE_INSTRUCTION_FIRE_NOW);

        $this->store->storeTrigger($trigger);
        $this->store->pauseTrigger($key);

        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key)->getState());

        $this->store->resumeTrigger($key);

        $trigger = $this->store->retrieveTrigger($key);

        $this->assertSame(Trigger::STATE_WAITING, $trigger->getState());
        // new next fire time was updated and it is closer to now
        $this->assertEquals(time(), $trigger->getNextFireTime()->format('U'), '', 10);
    }

    public function testOnResumeTriggerShouldUpdateMisfiredTriggerAndSetStateComplete()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setNextFireTime(new \DateTime('-1 hours'));
        $trigger->setEndTime(new \DateTime('-10 minutes'));
        $trigger->setMisfireInstruction(SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT);

        $this->store->storeTrigger($trigger);
        $this->store->pauseTrigger($key);

        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key)->getState());

        $this->store->resumeTrigger($key);

        $trigger = $this->store->retrieveTrigger($key);

        $this->assertSame(Trigger::STATE_COMPLETE, $trigger->getState());
        $this->assertNull($trigger->getNextFireTime());
    }

    public function testOnResumeJobShouldResumeAllAssociatedTriggers()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');
        $jobKey = new Key('name', 'group');

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setJobKey($jobKey);
        $trigger1->setNextFireTime(new \DateTime('+1 minute'));

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setJobKey($jobKey);
        $trigger2->setNextFireTime(new \DateTime('+1 minute'));

        $job = new JobDetail();
        $job->setKey($jobKey);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);
        $this->store->storeJob($job);

        $this->store->pauseJob($jobKey);

        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key2)->getState());

        $this->store->resumeJob($jobKey);

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key2)->getState());
    }

    public function testShouldPauseAllTriggers()
    {
        $key1 = new Key('name1', 'group1');
        $key2 = new Key('name2', 'group2');

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setNextFireTime(new \DateTime('+1 minute'));

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setNextFireTime(new \DateTime('+1 minute'));

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key2)->getState());
        $this->assertEmpty($this->store->getPausedTriggerGroups());

        $this->store->pauseAll();

        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key2)->getState());
        $this->assertSame(['group1', 'group2', '_$_ALL_GROUPS_PAUSED_$_'], $this->store->getPausedTriggerGroups());
    }

    public function testShouldResumeAllTriggers()
    {
        $key1 = new Key('name1', 'group1');
        $key2 = new Key('name2', 'group2');

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setNextFireTime(new \DateTime('+1 minute'));

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setNextFireTime(new \DateTime('+1 minute'));

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);

        $this->store->pauseAll();

        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key2)->getState());
        $this->assertSame(['group1', 'group2', '_$_ALL_GROUPS_PAUSED_$_'], $this->store->getPausedTriggerGroups());

        $this->store->resumeAll();

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key2)->getState());
        $this->assertEmpty($this->store->getPausedTriggerGroups());
    }

    public function testShouldReturnAllTriggersForThisJob()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');
        $key3 = new Key('name3', 'group');
        $jobKey = new Key('name', 'group');

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setJobKey(clone $jobKey);

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setJobKey(clone $jobKey);

        $trigger3 = new SimpleTrigger();
        $trigger3->setKey($key3);

        $job = new JobDetail();
        $job->setKey($jobKey);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);
        $this->store->storeTrigger($trigger3);
        $this->store->storeJob($job);

        $triggers = $this->store->getTriggersForJob($jobKey);

        $this->assertCount(2, $triggers);
    }

    public function testShouldReturnAllJobGroupNames()
    {
        $job1 = new JobDetail();
        $job1->setKey(new Key('name', 'group1'));

        $job2 = new JobDetail();
        $job2->setKey(new Key('name', 'group2'));

        $this->store->storeJob($job1);
        $this->store->storeJob($job2);

        $this->assertEquals(['group1', 'group2'], $this->store->getJobGroupNames());
    }

    public function testShouldReturnAllTriggerGroupNames()
    {
        $trigger1 = new SimpleTrigger();
        $trigger1->setKey(new Key('name', 'group1'));

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey(new Key('name', 'group2'));

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);

        $this->assertEquals(['group1', 'group2'], $this->store->getTriggerGroupNames());
    }

    public function testShouldReturnTriggerState()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);

        $this->store->storeTrigger($trigger);
        $this->store->pauseTrigger($key);

        $this->assertEquals(Trigger::STATE_PAUSED, $this->store->getTriggerState($key));
    }

    public function testOnGetTriggerStateShouldThrowExceptionIfTriggerDoesNotExist()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('There is no trigger with key: "group.name"');

        $this->store->getTriggerState(new Key('name', 'group'));
    }

    public function testShouldReturnAllCalendarNames()
    {
        $this->store->storeCalendar('cal1', new HolidayCalendar());
        $this->store->storeCalendar('cal2', new HolidayCalendar());

        $this->assertEquals(['cal1', 'cal2'], $this->store->getCalendarNames());
    }

    public function testShouldResetTriggerFromErrorState()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);

        $this->store->storeTrigger($trigger);

        // force trigger error state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ERROR,
            ]
        ]);

        // guard
        $this->assertSame(Trigger::STATE_ERROR, $this->store->retrieveTrigger($key)->getState());

        // test
        $this->store->resetTriggerFromErrorState($key);

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key)->getState());
    }

    public function testOnResetTriggerFromErrorStateShouldSetStatePausedIfGroupPaused()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);

        $this->store->storeTrigger($trigger);
        $this->store->insertPausedTriggerGroup('group');

        // force trigger error state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ERROR,
            ]
        ]);

        // guard
        $this->assertSame(Trigger::STATE_ERROR, $this->store->retrieveTrigger($key)->getState());

        // test
        $this->store->resetTriggerFromErrorState($key);

        $this->assertSame(Trigger::STATE_PAUSED, $this->store->retrieveTrigger($key)->getState());
    }

    public function testOnResetTriggerFromErrorStateShouldThrowExceptionIfTriggerDoesNotExist()
    {
        $this->expectException(SchedulerException::class);
        $this->expectExceptionMessage('There is no trigger with identity: "group.name"');

        // test
        $this->store->resetTriggerFromErrorState(new Key('name', 'group'));
    }

    public function testShouldAcquireNextTriggers()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');

        $trigger1 = new SimpleTrigger();
        $trigger1->setNextFireTime(new \DateTime());
        $trigger1->setKey($key1);

        $trigger2 = new SimpleTrigger();
        $trigger2->setNextFireTime(new \DateTime('+10 minutes'));
        $trigger2->setKey($key2);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key2)->getState());

        $triggers = $this->store->acquireNextTriggers(time() + 10, 10, 0);

        $this->assertCount(1, $triggers);
        $this->assertSame((string) $key1, (string) $triggers[0]->getKey());
        $this->assertSame(Trigger::STATE_ACQUIRED, $this->store->retrieveTrigger($key1)->getState());
    }

    public function testShouldAcquireMisfiredTriggers()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');
        $key3 = new Key('name3', 'group');

        $trigger1 = new SimpleTrigger();
        $trigger1->setNextFireTime(new \DateTime('-1 hour'));
        $trigger1->setKey($key1);

        $trigger2 = new SimpleTrigger();
        $trigger2->setNextFireTime(new \DateTime());
        $trigger2->setKey($key2);

        $trigger3 = new SimpleTrigger();
        $trigger3->setNextFireTime(new \DateTime('+1 hour'));
        $trigger3->setKey($key3);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);
        $this->store->storeTrigger($trigger3);

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key2)->getState());
        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key3)->getState());

        $triggers = $this->store->acquireNextTriggers(time() + 10, 10, 0);

        $this->assertCount(2, $triggers);
        $this->assertSame((string) $key2, (string) $triggers[0]->getKey());
        $this->assertSame((string) $key1, (string) $triggers[1]->getKey());
        $this->assertSame(Trigger::STATE_ACQUIRED, $this->store->retrieveTrigger($key2)->getState());
        $this->assertSame(Trigger::STATE_ACQUIRED, $this->store->retrieveTrigger($key1)->getState());
    }

    public function testShouldNotAcquireMisfiredTriggersIfThereIsNoFreeSpace()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');
        $key3 = new Key('name3', 'group');

        $trigger1 = new SimpleTrigger();
        $trigger1->setNextFireTime(new \DateTime('-1 hour'));
        $trigger1->setKey($key1);

        $trigger2 = new SimpleTrigger();
        $trigger2->setNextFireTime(new \DateTime());
        $trigger2->setKey($key2);

        $trigger3 = new SimpleTrigger();
        $trigger3->setNextFireTime(new \DateTime('+1 hour'));
        $trigger3->setKey($key3);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);
        $this->store->storeTrigger($trigger3);

        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key2)->getState());
        $this->assertSame(Trigger::STATE_WAITING, $this->store->retrieveTrigger($key3)->getState());

        $triggers = $this->store->acquireNextTriggers(time() + 10, 1, 0); // only one trigger

        $this->assertCount(1, $triggers);
        $this->assertSame((string) $key2, (string) $triggers[0]->getKey());
        $this->assertSame(Trigger::STATE_ACQUIRED, $this->store->retrieveTrigger($key2)->getState());
    }

    public function testOnTriggerFiredShouldCreateFiredTrigger()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setRepeatCount(0);
        $trigger->setStartTime(new \DateTime());
        $trigger->setRepeatInterval(10);
        $trigger->computeFirstFireTime();

        $this->store->storeTrigger($trigger);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $firedTriggers = $this->store->triggersFired([$trigger], time() + 30);

        $this->assertCount(1, $firedTriggers);

        $this->assertNotNull($this->store->retrieveFireTrigger($firedTriggers[0]->getFireInstanceId()));
    }

    public function testOnTriggerFiredShouldCreateSeveralFiredTriggerIfNextFireTimeBeforeNoLaterThan()
    {
        $key = new Key('name', 'group');

        $startTime = new \DateTime('2012-12-12 00:00:00');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setRepeatInterval(10);
        $trigger->setRepeatCount(SimpleTrigger::REPEAT_INDEFINITELY);
        $trigger->setStartTime($startTime);
        $trigger->setNextFireTime($startTime);
        $trigger->setMisfireInstruction(Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY);

        $this->store->storeTrigger($trigger);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $noLaterThan = ((int) $startTime->format('U')) + 25;

        $firedTriggers = $this->store->triggersFired([$trigger], $noLaterThan);

        $this->assertCount(3, $firedTriggers);
    }

    public function testOnTriggerFiredShouldReturnEmptyIfThereIsNotTriggerWithKey()
    {
        $key = new Key('name', 'group');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);

        // $this->store->storeTrigger($trigger); do not store this trigger

        $this->assertEmpty($this->store->triggersFired([$trigger], time() + 100));
    }

    public function testOnTriggerFiredShouldReturnEmptyIfTriggerStateIsNotAcquired()
    {
        $key = new Key('name', 'group');

        $startTime = new \DateTime();

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setRepeatInterval(10);
        $trigger->setRepeatCount(SimpleTrigger::REPEAT_INDEFINITELY);
        $trigger->setStartTime($startTime);
        $trigger->setNextFireTime($startTime);
        $trigger->setMisfireInstruction(Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY);

        $this->store->storeTrigger($trigger);

        $this->assertEmpty($this->store->triggersFired([$trigger], time() + 100));
    }

    public function testOnTriggerFiredShouldReturnEmptyIfCalendarWasNotFound()
    {
        $key = new Key('name', 'group');

        $startTime = new \DateTime();

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setRepeatInterval(10);
        $trigger->setRepeatCount(SimpleTrigger::REPEAT_INDEFINITELY);
        $trigger->setStartTime($startTime);
        $trigger->setNextFireTime($startTime);
        $trigger->setMisfireInstruction(Trigger::MISFIRE_INSTRUCTION_IGNORE_MISFIRE_POLICY);
        $trigger->setCalendarName('missing-calendar');

        $this->store->storeTrigger($trigger);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $this->assertEmpty($this->store->triggersFired([$trigger], time() + 100));
    }

    public function testOnTriggerFiredShouldSetStateCompletedIfTriggerNextFireTimeIsNull()
    {
        $key = new Key('name', 'group');

        $startTime = new \DateTime();

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setRepeatInterval(10);
        $trigger->setRepeatCount(0);
        $trigger->setStartTime($startTime);
        $trigger->setNextFireTime($startTime);

        $this->store->storeTrigger($trigger);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $fireTriggers = $this->store->triggersFired([$trigger], time() + 100);

        $this->assertCount(1, $fireTriggers);
        $this->assertSame(Trigger::STATE_COMPLETE, $this->store->retrieveTrigger($key)->getState());
    }

    public function testOnTriggerFiredShouldUpdateMisfiredTrigger()
    {
        $key = new Key('name', 'group');

        $startTime = new \DateTime('-1 hour');

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setRepeatInterval(10);
        $trigger->setRepeatCount(1);
        $trigger->setStartTime($startTime);
        $trigger->setNextFireTime($startTime);
        $trigger->setMisfireInstruction(SimpleTrigger::MISFIRE_INSTRUCTION_RESCHEDULE_NOW_WITH_EXISTING_REPEAT_COUNT);

        $this->store->storeTrigger($trigger);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $fireTriggers = $this->store->triggersFired([$trigger], time() + 100);

        // fires now and plus one repeat
        $this->assertCount(2, $fireTriggers);
    }

    public function testOnTriggerJobCompleteShouldRemoveTriggerAndFiredTrigger()
    {
        $key = new Key('name', 'group');

        $startTime = new \DateTime();

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setJobKey(new Key('name', 'group'));
        $trigger->setRepeatInterval(10);
        $trigger->setRepeatCount(0);
        $trigger->setStartTime($startTime);
        $trigger->setNextFireTime($startTime);

        $this->store->storeTrigger($trigger);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $fireTriggers = $this->store->triggersFired([$trigger], time() + 100);

        $this->assertCount(1, $fireTriggers);

        // test
        $this->store->triggeredJobComplete($fireTriggers[0], new JobDetail(), CompletedExecutionInstruction::DELETE_TRIGGER);

        $this->assertNull($this->store->retrieveTrigger($key));
        $this->assertNotEmpty($fireTriggers[0]->getFireInstanceId());
        $this->assertNull($this->store->retrieveFireTrigger($fireTriggers[0]->getFireInstanceId()));
    }

    public function testOnTriggerJobCompleteShouldSetTriggerStateCompleteAndDeleteFiredTrigger()
    {
        $key = new Key('name', 'group');

        $startTime = new \DateTime();

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setRepeatInterval(10);
        $trigger->setRepeatCount(0);
        $trigger->setStartTime($startTime);
        $trigger->setNextFireTime($startTime);

        $this->store->storeTrigger($trigger);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $fireTriggers = $this->store->triggersFired([$trigger], time() + 100);

        $this->assertCount(1, $fireTriggers);

        // test
        $this->store->triggeredJobComplete($fireTriggers[0], new JobDetail(), CompletedExecutionInstruction::SET_TRIGGER_COMPLETE);

        $this->assertSame(Trigger::STATE_COMPLETE, $this->store->retrieveTrigger($key)->getState());
        $this->assertNotEmpty($fireTriggers[0]->getFireInstanceId());
        $this->assertNull($this->store->retrieveFireTrigger($fireTriggers[0]->getFireInstanceId()));
    }

    public function testOnTriggerJobCompleteShouldSetTriggerStateErrorAndDeleteFiredTrigger()
    {
        $key = new Key('name', 'group');

        $startTime = new \DateTime();

        $trigger = new SimpleTrigger();
        $trigger->setKey($key);
        $trigger->setRepeatInterval(10);
        $trigger->setRepeatCount(0);
        $trigger->setStartTime($startTime);
        $trigger->setNextFireTime($startTime);

        $this->store->storeTrigger($trigger);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key->getName(),
            'group' => $key->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $fireTriggers = $this->store->triggersFired([$trigger], time() + 100);

        $this->assertCount(1, $fireTriggers);

        // test
        $fireTriggers[0]->setErrorMessage('the error message');
        $this->store->triggeredJobComplete($fireTriggers[0], new JobDetail(), CompletedExecutionInstruction::SET_TRIGGER_ERROR);

        $trigger = $this->store->retrieveTrigger($key);

        $this->assertSame(Trigger::STATE_ERROR, $trigger->getState());
        $this->assertSame('the error message', $trigger->getErrorMessage());
        $this->assertNotEmpty($fireTriggers[0]->getFireInstanceId());
        $this->assertNull($this->store->retrieveFireTrigger($fireTriggers[0]->getFireInstanceId()));
    }

    public function testOnTriggerJobCompleteShouldSetAllJobTriggersStateCompleteAndDeleteFiredTrigger()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');
        $jobKey = new Key('job-name', 'group');

        $startTime = new \DateTime();

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setJobKey(clone $jobKey);
        $trigger1->setRepeatInterval(10);
        $trigger1->setRepeatCount(0);
        $trigger1->setStartTime($startTime);
        $trigger1->setNextFireTime($startTime);

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setJobKey(clone $jobKey);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key1->getName(),
            'group' => $key1->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $fireTriggers = $this->store->triggersFired([$trigger1], time() + 100);

        $this->assertCount(1, $fireTriggers);

        // test
        $this->store->triggeredJobComplete($fireTriggers[0], new JobDetail(), CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_COMPLETE);

        $this->assertSame(Trigger::STATE_COMPLETE, $this->store->retrieveTrigger($key1)->getState());
        $this->assertSame(Trigger::STATE_COMPLETE, $this->store->retrieveTrigger($key2)->getState());
        $this->assertNotEmpty($fireTriggers[0]->getFireInstanceId());
        $this->assertNull($this->store->retrieveFireTrigger($fireTriggers[0]->getFireInstanceId()));
    }

    public function testOnTriggerJobCompleteShouldSetAllJobTriggersStateErrorAndDeleteFiredTrigger()
    {
        $key1 = new Key('name1', 'group');
        $key2 = new Key('name2', 'group');
        $jobKey = new Key('job-name', 'group');

        $startTime = new \DateTime();

        $trigger1 = new SimpleTrigger();
        $trigger1->setKey($key1);
        $trigger1->setJobKey(clone $jobKey);
        $trigger1->setRepeatInterval(10);
        $trigger1->setRepeatCount(0);
        $trigger1->setStartTime($startTime);
        $trigger1->setNextFireTime($startTime);

        $trigger2 = new SimpleTrigger();
        $trigger2->setKey($key2);
        $trigger2->setJobKey(clone $jobKey);

        $this->store->storeTrigger($trigger1);
        $this->store->storeTrigger($trigger2);

        // force acquired state
        $this->res->getTriggerStorage()->getCollection()->updateOne([
            'name' => $key1->getName(),
            'group' => $key1->getGroup(),
        ], [
            '$set' => [
                'state' => Trigger::STATE_ACQUIRED,
            ]
        ]);

        $fireTriggers = $this->store->triggersFired([$trigger1], time() + 100);

        $this->assertCount(1, $fireTriggers);

        // test
        $fireTriggers[0]->setErrorMessage('the error message');
        $this->store->triggeredJobComplete($fireTriggers[0], new JobDetail(), CompletedExecutionInstruction::SET_ALL_JOB_TRIGGERS_ERROR);


        $trigger1 = $this->store->retrieveTrigger($key1);
        $this->assertSame(Trigger::STATE_ERROR, $trigger1->getState());
        $this->assertSame('the error message', $trigger1->getErrorMessage());

        $trigger2 = $this->store->retrieveTrigger($key2);
        $this->assertSame(Trigger::STATE_ERROR, $trigger2->getState());
        $this->assertSame('the error message', $trigger2->getErrorMessage());

        $this->assertNotEmpty($fireTriggers[0]->getFireInstanceId());
        $this->assertNull($this->store->retrieveFireTrigger($fireTriggers[0]->getFireInstanceId()));
    }
}
