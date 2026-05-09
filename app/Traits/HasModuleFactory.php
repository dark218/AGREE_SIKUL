<?php

namespace App\Traits;

trait HasModuleFactory
{
    protected static function newFactory()
    {
        $factoryClass = 'Database\\Factories\\' .class_basename(static::class) . 'Factory';
        if (!class_exists($factoryClass)) {
            throw new \RuntimeException("Factory class {$factoryClass} not found for model".static::class);
        }
        return $factoryClass::new();
    }
}
