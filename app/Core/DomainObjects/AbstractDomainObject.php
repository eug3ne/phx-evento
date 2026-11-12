<?php

declare(strict_types=1);

namespace App\Core\DomainObjects;

use ReflectionClass;
use ReflectionProperty;

abstract class AbstractDomainObject
{
    /**
     * Hydrate a DomainObject from an associative array or Eloquent model attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        $reflection = new ReflectionClass(static::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE) as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $attributes)) {
                $property->setValue($instance, $attributes[$name]);
            }
        }

        return $instance;
    }

    /**
     * Export DomainObject properties as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $result = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isInitialized($this)) {
                $result[$property->getName()] = $property->getValue($this);
            }
        }

        return $result;
    }
}
