<?php
namespace Quartz\Tests\Core;

use PHPUnit\Framework\TestCase;
use Quartz\Core\Key;

class KeyTest extends TestCase
{
    public function testCouldBeConstructedWithOnlyName()
    {
        $key = new Key('name');

        $this->assertSame('name', $key->getName());
        $this->assertSame('DEFAULT', $key->getGroup());
    }

    public function testCouldBeConstructedWithNameAndGroup()
    {
        $key = new Key('name', 'group');

        $this->assertSame('name', $key->getName());
        $this->assertSame('group', $key->getGroup());
    }

    public function testShouldThrowExceptionIfNameIsEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');

        new Key('');
    }

    public function testCouldCompareKeys()
    {
        $this->assertTrue((new Key('name', 'group'))->equals(new Key('name', 'group')));
        $this->assertFalse((new Key('name1', 'group'))->equals(new Key('name', 'group')));
    }

    public function testCouldCastObjectToString()
    {
        $this->assertSame('group.name', (string) new Key('name', 'group'));
    }

    public function testShouldGenerateUniqueNames()
    {
        $name1 = Key::createUniqueName();
        $name2 = Key::createUniqueName();

        $this->assertNotEmpty($name1);
        $this->assertNotEmpty($name2);
        $this->assertNotEquals($name1, $name2);
    }
}
