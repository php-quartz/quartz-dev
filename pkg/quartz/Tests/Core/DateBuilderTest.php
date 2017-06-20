<?php
namespace Quartz\Tests\Core;

use PHPUnit\Framework\TestCase;
use Quartz\Core\DateBuilder;
use Quartz\Core\IntervalUnit;

class DateBuilderTest extends TestCase
{
    public function testShouldReturnMaxYearAsNowPlus100Yars()
    {
        $year = (int) (new \DateTime())->format('Y');

        $this->assertSame($year+100, DateBuilder::MAX_YEAR());
    }

    public function testOnValidateDayOfWeekShouldThrowExceptionIfInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid day of week: "100"');

        DateBuilder::validateDayOfWeek(100);
    }

    public function testOnValidateDayOfWeekShouldNotThrowExceptionIfValidValue()
    {
        DateBuilder::validateDayOfWeek(DateBuilder::MONDAY);
        DateBuilder::validateDayOfWeek(DateBuilder::THURSDAY);
        DateBuilder::validateDayOfWeek(DateBuilder::WEDNESDAY);
        DateBuilder::validateDayOfWeek(DateBuilder::THURSDAY);
        DateBuilder::validateDayOfWeek(DateBuilder::FRIDAY);
        DateBuilder::validateDayOfWeek(DateBuilder::SATURDAY);
        DateBuilder::validateDayOfWeek(DateBuilder::SUNDAY);
    }

    public function testOnValidateHourShouldThrowExceptionIfInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid hour (must be >= 0 and <= 23).');

        DateBuilder::validateHour(24);
    }

    public function testOnValidateHourShouldNotThrowExceptionIfValidValue()
    {
        DateBuilder::validateHour(0);
        DateBuilder::validateHour(23);
    }

    public function testOnValidateMinuteShouldThrowExceptionIfInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid minute (must be >= 0 and <= 59).');

        DateBuilder::validateMinute(60);
    }

    public function testOnValidateMinuteShouldNotThrowExceptionIfValidValue()
    {
        DateBuilder::validateMinute(0);
        DateBuilder::validateMinute(59);
    }

    public function testOnValidateSecondShouldThrowExceptionIfInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid second (must be >= 0 and <= 59).');

        DateBuilder::validateSecond(60);
    }

    public function testOnValidateSecondShouldNotThrowExceptionIfValidValue()
    {
        DateBuilder::validateSecond(0);
        DateBuilder::validateSecond(59);
    }

    public function testOnValidateDayOfMonthShouldThrowExceptionIfInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid day of month (must be >= 1 and <= 31).');

        DateBuilder::validateDayOfMonth(32);
    }

    public function testOnValidateDayOfMonthShouldNotThrowExceptionIfValidValue()
    {
        DateBuilder::validateDayOfMonth(1);
        DateBuilder::validateDayOfMonth(31);
    }

    public function testOnValidateMonthShouldThrowExceptionIfInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid month (must be >= 1 and <= 12.');

        DateBuilder::validateMonth(13);
    }

    public function testOnValidateMonthShouldNotThrowExceptionIfValidValue()
    {
        DateBuilder::validateMonth(1);
        DateBuilder::validateMonth(12);
    }

    public function testOnValidateYearShouldThrowExceptionIfInvalidValue()
    {
        $year = (int) (new \DateTime())->format('Y');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid year (must be >= 0 and <= %d', $year + 100));

        DateBuilder::validateYear($year + 200);
    }

    public function testOnValidateYearShouldNotThrowExceptionIfValidValue()
    {
        $year = (int) (new \DateTime())->format('Y');

        DateBuilder::validateYear(0);
        DateBuilder::validateYear($year + 100);
    }

    public function testOnValidateIntervalUnitShouldThrowExceptionIfInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid interval unit.');

        DateBuilder::validateIntervalUnit(100);
    }

    public function testOnValidateIntervalUnitShouldNotThrowExceptionIfValidValue()
    {
        DateBuilder::validateIntervalUnit(IntervalUnit::SECOND);
        DateBuilder::validateIntervalUnit(IntervalUnit::MINUTE);
        DateBuilder::validateIntervalUnit(IntervalUnit::HOUR);
        DateBuilder::validateIntervalUnit(IntervalUnit::DAY);
        DateBuilder::validateIntervalUnit(IntervalUnit::WEEK);
        DateBuilder::validateIntervalUnit(IntervalUnit::MONTH);
        DateBuilder::validateIntervalUnit(IntervalUnit::YEAR);
    }
}
