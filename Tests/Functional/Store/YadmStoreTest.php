<?php
namespace Quartz\Tests\Functional;

use Quartz\Calendar\HolidayCalendar;
use Quartz\Core\JobPersistenceException;
use Quartz\Core\Key;
use Quartz\Core\Trigger;
use Quartz\JobDetail\JobDetail;
use Quartz\Store\ObjectAlreadyExistsException;
use Quartz\Store\YadmStore;
use Quartz\Store\YadmStoreResource;
use Quartz\Triggers\SimpleTrigger;

class YadmStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var YadmStore
     */
    private $store;

    protected function setUp()
    {
        parent::setUp();

        $config = [
            'uri' => sprintf('mongodb://%s:%s', getenv('MONGODB_HOST'), getenv('MONGODB_PORT')),
            'dbName' => getenv('MONGODB_DB')
        ];

        $this->store = new YadmStore(new YadmStoreResource($config));
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
}
