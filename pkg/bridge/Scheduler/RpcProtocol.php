<?php
namespace Quartz\Bridge\Scheduler;

use function Makasim\Values\get_values;
use function Makasim\Values\set_values;
use Quartz\Core\Model;
use Quartz\ModelClassFactory;

class RpcProtocol
{
    public function encodeRequest($method, array $args)
    {
        $encodedArgs = [];
        foreach ($args as $arg) {
            $encodedArgs[] = $this->encodeValue($arg);
        }

        return [
            'method' => $method,
            'args' => $encodedArgs,
        ];
    }

    public function decodeRequest($data)
    {
        if (false == isset($data['method'])) {
            throw new \InvalidArgumentException('Method property is not set');
        }

        if (false == isset($data['args'])) {
            throw new \InvalidArgumentException('Args property is not set');
        }

        return [
            'method' => $data['method'],
            'args' => $this->decodeValue($data['args']),
        ];
    }

    public function encodeValue($data)
    {
        if (is_scalar($data) || is_null($data)) {
            return $data;
        } elseif (is_object($data)) {
            if ($data instanceof Model) {
                return [
                    '__values__' => get_values($data),
                ];
            } elseif ($data instanceof \Exception) {
                return [
                    '__exception__' => [
                        'class' => get_class($data),
                        'message' => $data->getMessage(),
                        'code' => $data->getCode(),
                    ]
                ];
            } elseif ($data instanceof \DateTime) {
                return [
                    '__datetime__' => [
                        'iso' => $data->format(DATE_ISO8601),
                        'unix' => $data->format('U'),
                        'tz' => $data->getTimezone()->getName(),
                    ]
                ];
            }

            throw new \InvalidArgumentException('Object arguments are not allowed');
        } elseif (is_array($data)) {
            $result = [];

            foreach ($data as $key => $value) {
                $result[$key] = $this->encodeValue($value);
            }

            return $result;
        } else {
            throw new \InvalidArgumentException('Invalid argument');
        }
    }

    public function decodeValue($data)
    {
        if (is_scalar($data) || is_null($data)) {
            return $data;
        } elseif (is_array($data)) {
            // values object
            if (isset($data['__values__'])) {
                $class = ModelClassFactory::getClass($data['__values__']);
                $rc = new \ReflectionClass($class);
                $object = $rc->newInstanceWithoutConstructor();
                set_values($object, $data['__values__']);

                return $object;
            }

            // datetime
            if (isset($data['__datetime__'])) {
                $time = new \DateTime('@'.$data['__datetime__']['unix']);
                $time->setTimezone(new \DateTimeZone($data['__datetime__']['tz']));

                return $time;
            }

            // exception
            if (isset($data['__exception__'])) {
                return $this->decodeException($data['__exception__']);
            }

            // just an array
            $result = [];
            foreach ($data as $key => $value) {
                $decValue = $this->decodeValue($value);
                // only top level exception is allowed

                if ($decValue instanceof \Exception) {
                    throw new \InvalidArgumentException('Only top level exception is allowed');
                }

                $result[$key] = $decValue;
            }

            return $result;
        } else {
            throw new \InvalidArgumentException('Unexpected value');
        }
    }

    private function decodeException(array $data)
    {
        if (false == isset($data['class'])) {
            throw new \InvalidArgumentException('Exception class property is not set');
        }

        if (false == isset($data['message'])) {
            throw new \InvalidArgumentException('Exception message property is not set');
        }

        if (false == isset($data['code'])) {
            throw new \InvalidArgumentException('Exception code property is not set');
        }

        if (class_exists($data['class'])) {
            return new $data['class']($data['message'], $data['code']);
        } else {
            return new \Exception(json_encode($data));
        }
    }
}
