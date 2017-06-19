<?php
namespace Quartz\Bridge\Tests\Scheduler;

use PHPUnit\Framework\TestCase;
use Quartz\Bridge\Scheduler\RpcProtocol;
use Quartz\Core\Key;
use Quartz\Core\SchedulerException;
use Quartz\JobDetail\JobDetail;

class RpcProtocolTest extends TestCase
{
    public function valuesDataProvider()
    {
        $t1 = [
            '__datetime__' => [
                'iso' => '2012-12-12T12:12:12+0000',
                'unix' => '1355314332',
                'tz' => 'UTC',
            ]
        ];

        $t2 = [
            '__datetime__' => [
                'iso' => '2012-12-12T12:12:12+0100',
                'unix' => '1355310732',
                'tz' => 'Europe/Paris',
            ]
        ];

        $e1 = [
            '__exception__' => [
                'class' => 'Quartz\Core\SchedulerException',
                'message' => 'message',
                'code' => 12345,
            ]
        ];

        $d1 = new JobDetail();
        $d1->setKey(new Key('name', 'group'));
        $d1->setDurable(true);
        $d1->setJobDataMap(['key' => 'value']);

        $d1e = [
            '__values__' => [
                'instance' => 'job-detail',
                'name' => 'name',
                'group' => 'group',
                'durable' => true,
                'jobDataMap' => [
                    'key' => 'value',
                ]
            ]
        ];

        $arrayOfValues = [
            new Key('name', 'group'),
            123,
            'string',
            new \DateTime('2012-12-12 12:12:12'),
        ];

        $arrayOfValuesEncoded = [
            [
                '__values__' => [
                    'instance' => 'key',
                    'name' => 'name',
                    'group' => 'group',
                ],
            ],
            123,
            'string',
            [
                '__datetime__' => [
                    'iso' => '2012-12-12T12:12:12+0000',
                    'unix' => '1355314332',
                    'tz' => 'UTC',
                ]
            ]

        ];

        return [
            [null, null],
            [123, 123],
            [123.45, 123.45],
            ['string', 'string'],
            [new \DateTime('2012-12-12 12:12:12'), $t1],
            [new \DateTime('2012-12-12 12:12:12', new \DateTimeZone('Europe/Paris')), $t2],
            [new SchedulerException('message', 12345), $e1],
            [$d1, $d1e],
            [$arrayOfValues, $arrayOfValuesEncoded],
        ];
    }

    /**
     * @dataProvider valuesDataProvider
     */
    public function testShouldEncodeValues($raw, $encoded)
    {
        $proto = new RpcProtocol();

        $this->assertSame($encoded, $proto->encodeValue($raw));
    }

    public function testEncodeValueShouldThrowExceptionIfValueIsResource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument');

        $proto = new RpcProtocol();
        $proto->encodeValue(fopen('php://memory', 'r'));
    }

    public function testEncodeValueShouldThrowExceptionIfValueIsObjectButNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Object arguments are not allowed');

        $proto = new RpcProtocol();
        $proto->encodeValue(new \stdClass());
    }

    public function scalarValuesDataProvider()
    {
        return [
            [null, null],
            [123, 123],
            [123.45, 123.45],
            ['string', 'string'],
        ];
    }

    /**
     * @dataProvider scalarValuesDataProvider
     */
    public function testShouldDecodeValues($decoded, $encoded)
    {
        $proto = new RpcProtocol();

        $this->assertEquals($decoded, $proto->decodeValue($encoded));
    }

    public function testShouldDecodeDateTimeValue()
    {
        $t1 = [
            '__datetime__' => [
                'iso' => '2012-12-12T12:12:12+0000',
                'unix' => '1355314332',
                'tz' => 'UTC',
            ]
        ];

        $t2 = [
            '__datetime__' => [
                'iso' => '2012-12-12T12:12:12+0100',
                'unix' => '1355310732',
                'tz' => 'Europe/Paris',
            ]
        ];

        $proto = new RpcProtocol();

        $this->assertEquals(new \DateTime('2012-12-12 12:12:12'), $proto->decodeValue($t1));
        $this->assertEquals(new \DateTime('2012-12-12 12:12:12', new \DateTimeZone('Europe/Paris')), $proto->decodeValue($t2));
    }

    public function testShouldDecodeValuesTraitData()
    {
        $d1e = [
            '__values__' => [
                'instance' => 'job-detail',
                'name' => 'name',
                'group' => 'group',
                'durable' => true,
                'jobDataMap' => [
                    'key' => 'value',
                ]
            ]
        ];

        $proto = new RpcProtocol();

        $result = $proto->decodeValue($d1e);

        $this->assertInstanceOf(JobDetail::class, $result);
        $this->assertSame(true, $result->isDurable());
        $this->assertSame(['key' => 'value'], $result->getJobDataMap());
        $this->assertSame('name', $result->getKey()->getName());
        $this->assertSame('group', $result->getKey()->getGroup());
    }

    public function testShouldDecodeExceptionData()
    {
        $e1 = [
            '__exception__' => [
                'class' => 'Quartz\Core\SchedulerException',
                'message' => 'message',
                'code' => 12345,
            ]
        ];

        $proto = new RpcProtocol();

        $result = $proto->decodeValue($e1);

        $this->assertInstanceOf(SchedulerException::class, $result);
        $this->assertSame('message', $result->getMessage());
        $this->assertSame(12345, $result->getCode());
    }

    public function testShouldDecodeArrayOfValues()
    {
        $values = [
            null,
            'string',
            123,
            123.45,
            [
                '__values__' => [
                    'instance' => 'key',
                    'name' => 'name',
                    'group' => 'group',
                ],
            ],
        ];

        $proto = new RpcProtocol();

        $result = $proto->decodeValue($values);

        $this->assertSame(null, $result[0]);
        $this->assertSame('string', $result[1]);
        $this->assertSame(123, $result[2]);
        $this->assertSame(123.45, $result[3]);
        $this->assertInstanceOf(Key::class, $result[4]);
        $this->assertSame('name', $result[4]->getName());
        $this->assertSame('group', $result[4]->getGroup());
    }

    public function testDecodeValueShouldThrowExceptionIfValueIsObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unexpected value');

        $proto = new RpcProtocol();
        $proto->decodeValue(new \stdClass());
    }

    public function testDecodeValueShouldThrowExceptionIfExceptionIsNotTopLevel()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only top level exception is allowed');

        $e1 = [
            [
                '__exception__' => [
                    'class' => 'Quartz\Core\SchedulerException',
                    'message' => 'message',
                    'code' => 12345,
                ]
            ]
        ];

        $proto = new RpcProtocol();
        $proto->decodeValue($e1);
    }

    public function testShouldEncodeRequest()
    {
        $proto = new RpcProtocol();

        $expectedResult = [
            'method' => 'methodName',
            'args' => ['arg1', 'arg2'],
        ];

        $this->assertSame($expectedResult, $proto->encodeRequest('methodName', ['arg1', 'arg2']));
    }

    public function testShouldDecodeRequest()
    {
        $proto = new RpcProtocol();

        $expectedResult = [
            'method' => 'methodName',
            'args' => ['arg1', 'arg2'],
        ];

        $this->assertSame($expectedResult, $proto->decodeRequest(['method' => 'methodName' , 'args' => ['arg1', 'arg2']]));
    }
}
