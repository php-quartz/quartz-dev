<?php declare(strict_types=1);

namespace Quartz\Bridge\Yadm;

use function Formapro\Values\build_object;
use Formapro\Yadm\Hydrator;
use Quartz\ModelClassFactory;

class ModelHydrator extends Hydrator
{
    public function __construct()
    {
    }

    public function create(array $values = [])
    {
        $class = ModelClassFactory::getClass($values);

        return $this->hydrate($values, build_object($class, $values));
    }
}